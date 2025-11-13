-- =====================================================
-- SISTEMA MULTIEMPRESA
-- Creación de tabla empresas y modificación de sucursales
-- =====================================================

-- 1. Crear tabla empresas
CREATE TABLE empresas (
    id_empresa SERIAL NOT NULL,
    razon_social VARCHAR(255) NOT NULL,
    nombre_comercial VARCHAR(255),
    ruc VARCHAR(50) NOT NULL,
    direccion TEXT,
    telefono VARCHAR(40),
    email VARCHAR(100),
    logo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id_empresa)
);

-- 2. Agregar campo id_empresa a sucursales
ALTER TABLE sucursales ADD COLUMN id_empresa INTEGER;

-- 3. Agregar foreign key
ALTER TABLE sucursales ADD FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresa);

-- 4. Crear índice para mejorar performance
CREATE INDEX idx_sucursales_empresa ON sucursales(id_empresa);

-- 5. Insertar datos de ejemplo
-- Empresa 1: UTIC
INSERT INTO empresas (razon_social, nombre_comercial, ruc, direccion, telefono, email, logo)
VALUES (
    'UNIVERSIDAD TECNOLOGICA INTERCONTINENTAL',
    'Empresa UTIC',
    '80000000-1',
    'Atyra 1750 c/Capitán Rivas',
    '021 200 100',
    'info@utic.edu.py',
    'https://www.utic.edu.py/v7/images/logosutic/Foto%20de%20perfil.png'
);

-- Empresa 2: J&O Departamentos
INSERT INTO empresas (razon_social, nombre_comercial, ruc, direccion, telefono, email, logo)
VALUES (
    'J&O DEPARTAMENTOS S.A.',
    'J&O DEPARTAMENTOS',
    '80000001-2',
    'Calle Leonardo Salinas, Barrio Kennedy - Capiatá',
    '0981 123 456',
    'info@jyo.com.py',
    'https://www.utic.edu.py/v7/images/logosutic/Foto%20de%20perfil.png'
);

-- 6. Actualizar sucursales existentes (asignar empresa 1 por defecto)
-- Si tienes sucursales existentes, asignarlas a la primera empresa
UPDATE sucursales SET id_empresa = 1 WHERE id_empresa IS NULL;

-- 7. Hacer el campo obligatorio después de asignar valores
ALTER TABLE sucursales ALTER COLUMN id_empresa SET NOT NULL;

COMMENT ON TABLE empresas IS 'Tabla que almacena la información de las empresas del sistema';
COMMENT ON COLUMN empresas.razon_social IS 'Razón social legal de la empresa';
COMMENT ON COLUMN empresas.nombre_comercial IS 'Nombre comercial con el que opera la empresa';
COMMENT ON COLUMN empresas.ruc IS 'Registro Único de Contribuyente';
COMMENT ON COLUMN empresas.logo IS 'URL o ruta del logo de la empresa';
