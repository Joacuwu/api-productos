-- API de productos · esquema MySQL 5.x / MariaDB

CREATE TABLE IF NOT EXISTS productos (
    id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    sku            VARCHAR(20)   NOT NULL,
    nombre         VARCHAR(120)  NOT NULL,
    categoria      VARCHAR(40)   NOT NULL,
    precio         DECIMAL(12,2) NOT NULL,
    stock          INT UNSIGNED  NOT NULL DEFAULT 0,
    activo         TINYINT(1)    NOT NULL DEFAULT 1,
    creado_en      DATETIME      NOT NULL,
    actualizado_en DATETIME      NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sku (sku),
    KEY idx_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO productos (sku, nombre, categoria, precio, stock, activo, creado_en, actualizado_en) VALUES
('ALM-001', 'Yerba mate 1 kg', 'Almacén', 4200.00, 120, 1, NOW(), NOW()),
('ALM-002', 'Aceite de girasol 1,5 L', 'Almacén', 3100.00, 80, 1, NOW(), NOW()),
('ALM-003', 'Fideos tirabuzón 500 g', 'Almacén', 1200.00, 200, 1, NOW(), NOW()),
('BEB-001', 'Gaseosa cola 2,25 L', 'Bebidas', 2600.00, 60, 1, NOW(), NOW()),
('BEB-002', 'Agua mineral 2 L', 'Bebidas', 1100.00, 150, 1, NOW(), NOW()),
('LIM-001', 'Lavandina 2 L', 'Limpieza', 1300.00, 90, 1, NOW(), NOW()),
('LIM-002', 'Jabón en polvo 3 kg', 'Limpieza', 8900.00, 25, 1, NOW(), NOW()),
('PER-001', 'Shampoo 400 ml', 'Perfumería', 3900.00, 0, 1, NOW(), NOW()),
('LIB-001', 'Resma A4 500 hojas', 'Librería', 7800.00, 40, 1, NOW(), NOW()),
('LIB-002', 'Cuaderno A4 rayado', 'Librería', 3500.00, 75, 0, NOW(), NOW());
