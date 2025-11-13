-- ============================================================
-- Script para crear permisos del módulo Plantillas de Contratos
-- ============================================================

-- Insertar permisos para el módulo de plantillas de contratos
-- Estos permisos siguen el patrón usado en el sistema: {modulo} {accion}

INSERT INTO permissions (name, guard_name, created_at, updated_at)
VALUES
    ('plantillas_contratos index', 'web', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('plantillas_contratos create', 'web', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('plantillas_contratos edit', 'web', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('plantillas_contratos destroy', 'web', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (name, guard_name) DO NOTHING;

-- ============================================================
-- Asignar permisos al rol de Administrador (si existe)
-- ============================================================

-- Obtener el ID del rol de Administrador y asignar todos los permisos
-- Nota: Ajusta el nombre del rol según tu configuración

DO $$
DECLARE
    admin_role_id INTEGER;
    permission_rec RECORD;
BEGIN
    -- Buscar el rol de administrador (ajusta el nombre según tu sistema)
    SELECT id INTO admin_role_id FROM roles WHERE name = 'Admin' OR name = 'Administrador' LIMIT 1;

    IF admin_role_id IS NOT NULL THEN
        -- Asignar cada permiso al rol de administrador
        FOR permission_rec IN
            SELECT id FROM permissions
            WHERE name LIKE 'plantillas_contratos%'
        LOOP
            INSERT INTO role_has_permissions (permission_id, role_id)
            VALUES (permission_rec.id, admin_role_id)
            ON CONFLICT (permission_id, role_id) DO NOTHING;
        END LOOP;

        RAISE NOTICE 'Permisos asignados al rol de Administrador (ID: %)', admin_role_id;
    ELSE
        RAISE NOTICE 'No se encontró el rol de Administrador. Asigna manualmente los permisos.';
    END IF;
END $$;

-- ============================================================
-- Verificar permisos creados
-- ============================================================

SELECT id, name, guard_name, created_at
FROM permissions
WHERE name LIKE 'plantillas_contratos%'
ORDER BY name;

-- ============================================================
-- INSTRUCCIONES
-- ============================================================

/*
1. Ejecuta este script en tu base de datos PostgreSQL
2. Verifica que los permisos se hayan creado correctamente
3. Si tienes otros roles, asigna los permisos manualmente según necesites:

   Ejemplo para asignar permisos a un rol específico:

   INSERT INTO role_has_permissions (permission_id, role_id)
   SELECT p.id, r.id
   FROM permissions p, roles r
   WHERE p.name LIKE 'plantillas_contratos%'
     AND r.name = 'NombreDelRol'
   ON CONFLICT DO NOTHING;

4. Los usuarios con estos permisos podrán:
   - plantillas_contratos index: Ver listado de plantillas
   - plantillas_contratos create: Crear nuevas plantillas
   - plantillas_contratos edit: Editar plantillas existentes
   - plantillas_contratos destroy: Eliminar plantillas

5. Para ver los permisos de un usuario específico:

   SELECT u.name as usuario, p.name as permiso
   FROM users u
   JOIN model_has_roles mr ON mr.model_id = u.id
   JOIN roles r ON r.id = mr.role_id
   JOIN role_has_permissions rp ON rp.role_id = r.id
   JOIN permissions p ON p.id = rp.permission_id
   WHERE u.id = [ID_USUARIO]
     AND p.name LIKE 'plantillas_contratos%';
*/
