"""Loopback simulator only. Never connects to real hardware or the business DB."""
import socket
import struct
import subprocess
import threading
import time
from pathlib import Path

PHP = r'D:\phpstudy_pro\Extensions\php\php7.3.4nts\php.exe'
CLIENT = str(Path(__file__).with_name('water_alarm_client.php'))

def crc(data):
    value = 0xffff
    for byte in data:
        value ^= byte
        for _ in range(8):
            value = (value >> 1) ^ (0xa001 if value & 1 else 0)
    return struct.pack('<H', value)

def read_exact(conn, size):
    result = b''
    while len(result) < size:
        block = conn.recv(size - len(result))
        if not block:
            raise EOFError()
        result += block
    return result

def simulate(listener, mode, errors):
    try:
        conn, _ = listener.accept()
        with conn:
            conn.settimeout(2)
            registers = {1: 0, 2: 0, 3: 0, 31: 2, 32: 1, 33: 1, 34: 1, 35: 0}
            while True:
                try:
                    head = read_exact(conn, 2)
                except EOFError:
                    break
                if head[1] == 16:
                    middle = read_exact(conn, 5)
                    frame = head + middle + read_exact(conn, middle[4] + 2)
                    start, count = struct.unpack('>HH', middle[:4])
                    values = struct.unpack('>' + 'H' * count, frame[7:-2])
                    if start == 1:
                        assert values == (1, 1, 0xff01), 'wrong alarm defaults'
                    if start == 0x3d:
                        assert registers[31] == 6, 'UTF8 configured before text'
                        payload = frame[7:-2]
                        assert payload[0] == 255 and len(payload) == count * 2 and len(payload) <= 200
                        assert payload[1:].decode('utf-8').rstrip(' ') in ['水浸', '水浸!', 'a' * 199]
                        registers[33] = 2
                    else:
                        registers.update(dict(zip(range(start, start + count), values)))
                    response = frame[:6]
                else:
                    frame = head + read_exact(conn, 6)
                    start, value = struct.unpack('>HH', frame[2:6])
                    if head[1] == 6:
                        registers[start] = value
                        if start == 32 and value == 1:
                            registers[33] = 1
                        response = frame[:6]
                    else:
                        assert head == bytes([1, 3]) and start in (1, 33) and value == 3
                        response = bytes([1, 3, 6]) + struct.pack('>HHH', *(registers[i] for i in range(start, start + 3)))
                assert frame[-2:] == crc(frame[:-2]), 'request CRC mismatch'
                if mode == 'exception':
                    response = bytes([1, 0x83, 2])
                if mode == 'wrong_unit':
                    response = bytes([2]) + response[1:]
                if mode == 'wrong_function':
                    response = bytes([1, 6, 0, 1, 0, 1])
                if mode == 'bad_ack':
                    response = bytes([1, 6, 0, 2, 0, 1])
                if mode == 'bad_length':
                    response = bytes([1, 3, 2, 0, 1])
                packet = response + crc(response)
                if mode == 'bad_crc':
                    packet = packet[:-1] + bytes([packet[-1] ^ 255])
                if mode == 'timeout':
                    time.sleep(0.5)
                    break
                if mode == 'truncated':
                    conn.sendall(packet[:4])
                    break
                # Split every response to verify TCP has no message boundaries.
                for chunk in (packet[:1], packet[1:3], packet[3:]):
                    conn.sendall(chunk)
                    time.sleep(0.005)
                if mode not in ('happy', 'tts'):
                    break
    except Exception as exc:
        errors.append(exc)
    finally:
        listener.close()

for scenario in ['happy', 'tts', 'exception', 'wrong_unit', 'wrong_function', 'bad_ack', 'bad_length', 'bad_crc', 'timeout', 'truncated']:
    server = socket.socket()
    server.bind(('127.0.0.1', 0))
    server.listen(1)
    errors = []
    thread = threading.Thread(target=simulate, args=(server, scenario, errors), daemon=True)
    thread.start()
    result = subprocess.run([PHP, CLIENT, str(server.getsockname()[1]), scenario], capture_output=True, timeout=10)
    thread.join(3)
    assert result.returncode == 0, result.stdout.decode('utf-8', errors='replace') + result.stderr.decode('utf-8', errors='replace')
    assert not errors, errors
    print('PASS', scenario)
print('All transport, input validation and menu permission checks passed.')
