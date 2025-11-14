-- =====================================================
-- VERIFICACIÓN Y CORRECCIÓN DE TABLA EMPRESAS
-- Este script verifica si la tabla empresas existe y la crea si es necesario
-- =====================================================

-- 1. Verificar si la tabla empresas existe
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_tables WHERE schemaname = 'public' AND tablename = 'empresas') THEN
        -- La tabla no existe, crearla
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

        -- Crear índice para el RUC (debe ser único)
        CREATE UNIQUE INDEX idx_empresas_ruc ON empresas(ruc);

        RAISE NOTICE 'Tabla empresas creada exitosamente';
    ELSE
        RAISE NOTICE 'La tabla empresas ya existe';
    END IF;
END $$;

-- 2. Verificar si la columna id_empresa existe en sucursales
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT FROM information_schema.columns
        WHERE table_name = 'sucursales' AND column_name = 'id_empresa'
    ) THEN
        -- Agregar campo id_empresa a sucursales
        ALTER TABLE sucursales ADD COLUMN id_empresa INTEGER;
        RAISE NOTICE 'Campo id_empresa agregado a sucursales';
    ELSE
        RAISE NOTICE 'El campo id_empresa ya existe en sucursales';
    END IF;
END $$;

-- 3. Verificar si existe la foreign key
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints
        WHERE constraint_name = 'sucursales_id_empresa_fkey' AND table_name = 'sucursales'
    ) THEN
        -- Agregar foreign key solo si no existe
        ALTER TABLE sucursales ADD CONSTRAINT sucursales_id_empresa_fkey
            FOREIGN KEY (id_empresa) REFERENCES empresas(id_empresa);
        RAISE NOTICE 'Foreign key agregada a sucursales';
    ELSE
        RAISE NOTICE 'Foreign key ya existe en sucursales';
    END IF;
END $$;

-- 4. Insertar empresa de ejemplo si no existen empresas
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM empresas LIMIT 1) THEN
        -- Insertar empresa por defecto
        INSERT INTO empresas (razon_social, nombre_comercial, ruc, direccion, telefono, email, logo)
        VALUES (
            'MI EMPRESA S.A.',
            'Mi Empresa',
            '80000000-1',
            'Dirección de ejemplo',
            '021 000 000',
            'contacto@miempresa.com',
            NULL
        );
        RAISE NOTICE 'Empresa de ejemplo creada';
    ELSE
        RAISE NOTICE 'Ya existen empresas en la base de datos';
    END IF;
END $$;

-- 5. Asignar empresas a sucursales existentes (si no tienen)
DO $$
DECLARE
    primera_empresa_id INTEGER;
    sucursales_sin_empresa INTEGER;
BEGIN
    -- Obtener ID de la primera empresa
    SELECT id_empresa INTO primera_empresa_id FROM empresas ORDER BY id_empresa LIMIT 1;

    IF primera_empresa_id IS NOT NULL THEN
        -- Contar sucursales sin empresa
        SELECT COUNT(*) INTO sucursales_sin_empresa FROM sucursales WHERE id_empresa IS NULL;

        IF sucursales_sin_empresa > 0 THEN
            -- Asignar primera empresa a sucursales sin empresa
            UPDATE sucursales SET id_empresa = primera_empresa_id WHERE id_empresa IS NULL;
            RAISE NOTICE 'Asignadas % sucursales a la empresa %', sucursales_sin_empresa, primera_empresa_id;
        ELSE
            RAISE NOTICE 'Todas las sucursales ya tienen empresa asignada';
        END IF;
    END IF;
END $$;

-- 6. Verificar que todas las sucursales tengan empresa (hacer el campo NOT NULL)
DO $$
BEGIN
    -- Verificar que no haya sucursales sin empresa
    IF NOT EXISTS (SELECT 1 FROM sucursales WHERE id_empresa IS NULL) THEN
        -- Si todas las sucursales tienen empresa, hacer el campo NOT NULL
        ALTER TABLE sucursales ALTER COLUMN id_empresa SET NOT NULL;
        RAISE NOTICE 'Campo id_empresa configurado como NOT NULL';
    ELSE
        RAISE WARNING 'Hay sucursales sin empresa asignada, no se puede configurar como NOT NULL';
    END IF;
END $$;

-- 7. Mostrar resumen
SELECT
    (SELECT COUNT(*) FROM empresas) as total_empresas,
    (SELECT COUNT(*) FROM sucursales) as total_sucursales,
    (SELECT COUNT(*) FROM sucursales WHERE id_empresa IS NOT NULL) as sucursales_con_empresa;

-- Mostrar empresas creadas
SELECT * FROM empresas;
