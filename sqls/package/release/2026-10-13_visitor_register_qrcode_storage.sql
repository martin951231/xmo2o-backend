-- Persistent QR image storage: no expiry and no dependency on local upload files.
CREATE TABLE IF NOT EXISTS `pigcms_visitor_register_qrcode` (
  `qrcode_path` varchar(255) NOT NULL,
  `image_base64` mediumtext NOT NULL,
  PRIMARY KEY (`qrcode_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
