"""Integration test: outbound simulated device -> gateway <- PHP control client.
All listeners bind to loopback. No physical device, business DB or production host.
"""
import json
import socket
import struct
import subprocess
import threading
import time
from pathlib import Path

PHP = r'D:\phpstudy_pro\Extensions\php\php7.3.4nts\php.exe'
ROOT = Path(__file__).parent
SECRET = 'test-only-' * 5

def crc(data):
    value = 65535
    for byte in data:
        value ^= byte
        for _ in range(8):
            value = (value >> 1) ^ (0xa001 if value & 1 else 0)
    return struct.pack('<H', value)

def free_port():
    with socket.socket() as s:
        s.bind(('127.0.0.1', 0))
        return s.getsockname()[1]

def exact(s, size):
    out = b''
    while len(out) < size:
        data = s.recv(size - len(out))
        if not data:
            raise EOFError()
        out += data
    return out

class Device:
    def __init__(self, port, model=912):
        self.socket = socket.create_connection(('127.0.0.1', port), 2)
        self.socket.settimeout(10)
        self.registers = {0: model, 1: 0, 2: 0, 3: 0, 31: 2, 32: 1, 33: 1, 34: 1, 35: 0}
        self.requests = []
        self.handshake = threading.Event()
        self.closed = threading.Event()
        self.drop_next = False
        self.bad_crc = False
        self.errors = []
        self.thread = threading.Thread(target=self.run, daemon=True)
        self.thread.start()

    def run(self):
        try:
            while True:
                head = exact(self.socket, 2)
                if head[1] == 16:
                    part = exact(self.socket, 5)
                    frame = head + part + exact(self.socket, part[4] + 2)
                else:
                    frame = head + exact(self.socket, 6)
                assert frame[-2:] == crc(frame[:-2])
                self.requests.append(frame[:-2].hex())
                if self.drop_next:
                    self.drop_next = False
                    continue
                start, count = struct.unpack('>HH', frame[2:6])
                if head[1] == 3:
                    values = [self.registers[i] for i in range(start, start + count)]
                    reply = bytes([1, 3, 2 * count]) + struct.pack('>' + 'H' * count, *values)
                elif head[1] == 6:
                    self.registers[start] = count
                    if start == 32 and count == 1:
                        self.registers[33] = 1
                    reply = frame[:6]
                else:
                    if start == 61:
                        payload = frame[7:-2]
                        assert payload[0] == 255 and len(payload) == count * 2
                        assert self.registers[31] == 6
                        payload[1:].decode('utf8')
                        self.registers[33] = 2
                    else:
                        values = struct.unpack('>' + 'H' * count, frame[7:-2])
                        self.registers.update(dict(zip(range(start, start + count), values)))
                    reply = frame[:6]
                packet = reply + crc(reply)
                if self.bad_crc:
                    packet = packet[:-1] + bytes([packet[-1] ^ 255])
                    self.bad_crc = False
                # Exercise fragmentation between gateway and device.
                for block in (packet[:1], packet[1:3], packet[3:]):
                    self.socket.sendall(block)
                    time.sleep(.002)
                if start == 0:
                    self.handshake.set()
        except (EOFError, OSError):
            pass
        except Exception as error:
            self.errors.append(error)
        finally:
            self.closed.set()

    def close(self):
        try:
            self.socket.shutdown(socket.SHUT_RDWR)
        except OSError:
            pass
        self.socket.close()
        self.thread.join(2)
        assert not self.errors, self.errors

class Control:
    def __init__(self, port):
        self.socket = socket.create_connection(('127.0.0.1', port), 2)
        self.socket.settimeout(3)
        self.stream = self.socket.makefile('rb')
        self.seq = 0

    def send(self, op, **kwargs):
        self.seq += 1
        data = (json.dumps(dict(op=op, seq=self.seq, **kwargs)) + '\n').encode()
        # Also exercise fragmentation on the local control channel.
        self.socket.sendall(data[:3])
        self.socket.sendall(data[3:])
        line = self.stream.readline()
        assert line, 'unexpected control disconnect'
        response = json.loads(line)
        assert response['seq'] == self.seq
        return response

    def open(self, secret=SECRET):
        return self.send('open', secret=secret, device_key='primary')

    def close(self):
        self.stream.close()
        self.socket.close()

def start(allowed='127.0.0.1'):
    device_port, control_port = free_port(), free_port()
    while device_port == control_port:
        control_port = free_port()
    process = subprocess.Popen([PHP, str(ROOT / 'water_alarm_gateway_runner.php'), str(device_port), str(control_port), allowed], stdout=subprocess.DEVNULL, stderr=subprocess.PIPE)
    deadline = time.monotonic() + 5
    while time.monotonic() < deadline:
        assert process.poll() is None, process.stderr.read().decode(errors='replace')
        try:
            with socket.create_connection(('127.0.0.1', control_port), .1):
                return process, device_port, control_port
        except OSError:
            time.sleep(.05)
    raise AssertionError('gateway did not start')

def opened(port):
    c = Control(port)
    assert c.open()['ok']
    return c

for invalid_peers in ['', '*', 'not-an-ip']:
    result = subprocess.run([PHP, str(ROOT / 'water_alarm_gateway_runner.php'), str(free_port()), str(free_port()), invalid_peers], capture_output=True, timeout=3)
    assert result.returncode != 0
print('PASS missing or invalid allowed peer configuration fails closed')

process, dp, cp = start()
devices = []
try:
    c = Control(cp)
    assert not c.open()['ok']; c.close()
    print('PASS offline is rejected; nothing queued')
    wrong = Device(dp, model=999); devices.append(wrong)
    assert wrong.closed.wait(2)
    print('PASS wrong model is rejected')
    device = Device(dp); devices.append(device)
    assert device.handshake.wait(2)
    c = Control(cp)
    assert not c.open('invalid-secret')['ok']; c.close()
    assert len(device.requests) == 1
    print('PASS control authentication before any write')
    duplicate = Device(dp); devices.append(duplicate)
    assert duplicate.closed.wait(2)
    assert not device.closed.is_set()
    print('PASS duplicate device cannot replace active connection')
    c = opened(cp)
    other = Control(cp); assert not other.open()['ok']; other.close()
    assert c.send('exchange', pdu='0300010003')['ok']; c.close()
    print('PASS exclusive control lease and fragmented exchanges')
    time.sleep(.05)
    result = subprocess.run([PHP, str(ROOT / 'water_alarm_gateway_client.php'), str(cp)], capture_output=True, timeout=10)
    assert result.returncode == 0, result.stdout + result.stderr
    print(result.stdout.decode().strip())
    time.sleep(.05)
    c = opened(cp)
    before = len(device.requests)
    assert not c.send('exchange', pdu='0600050002')['ok']; c.close()
    assert len(device.requests) == before
    print('PASS unsupported device commands never forwarded')
    c = opened(cp)
    device.drop_next = True
    assert not c.send('exchange', pdu='0600010001')['ok']; c.close()
    assert device.closed.wait(2)
    print('PASS response timeout invalidates device connection')
    replacement = Device(dp); devices.append(replacement)
    assert replacement.handshake.wait(2)
    time.sleep(.1)
    assert replacement.requests == ['010300000001'], replacement.requests
    print('PASS reconnect does not replay old alarm commands')
    c = opened(cp)
    replacement.bad_crc = True
    assert not c.send('exchange', pdu='0300010003')['ok']; c.close()
    assert replacement.closed.wait(2)
    print('PASS invalid CRC closes session')
    final = Device(dp); devices.append(final)
    assert final.handshake.wait(2)
    before = len(final.requests)
    time.sleep(5.4)
    assert len(final.requests) > before and all(p == '010300000001' for p in final.requests)
    print('PASS idle heartbeat is read-only')
    c = opened(cp)
    final.close()
    time.sleep(.15)
    reconnected = Device(dp); devices.append(reconnected)
    assert reconnected.handshake.wait(2)
    assert c.stream.readline() == b''; c.close()
    print('PASS old control session cannot cross device reconnect')
finally:
    for d in devices:
        d.close()
    process.terminate(); process.wait(5)

process, dp, cp = start('192.0.2.1')
try:
    denied = Device(dp)
    assert denied.closed.wait(2) and not denied.requests
    denied.close()
    print('PASS source IP whitelist blocks unapproved device before probe')
finally:
    process.terminate(); process.wait(5)
print('All TCP CLIENT gateway integration checks passed.')
