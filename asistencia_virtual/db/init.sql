-- =========================================================
-- DB: asistencia_virtual (PostgreSQL)
-- Roles: admin, profesor, estudiante
-- Login: admin y profesor
-- Seed: 5 cursos x 5 estudiantes distintos
-- Restriccion: NO asistencia en fechas futuras (TRIGGERS)
-- =========================================================

DROP SCHEMA IF EXISTS public CASCADE;
CREATE SCHEMA public;

CREATE TABLE roles (
  id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  nombre VARCHAR(20) NOT NULL UNIQUE
);

CREATE TABLE usuarios (
  id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  rol_id INT NOT NULL REFERENCES roles(id),
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  activo BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cursos (
  id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL UNIQUE,
  profesor_id INT NOT NULL REFERENCES usuarios(id),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE matriculas (
  id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  curso_id INT NOT NULL REFERENCES cursos(id),
  estudiante_id INT NOT NULL REFERENCES usuarios(id),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_matricula UNIQUE (curso_id, estudiante_id)
);

CREATE TABLE asistencias (
  id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  curso_id INT NOT NULL REFERENCES cursos(id),
  estudiante_id INT NOT NULL REFERENCES usuarios(id),
  fecha DATE NOT NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'PRESENTE',
  marcado_por INT NOT NULL REFERENCES usuarios(id),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_asistencia UNIQUE (curso_id, estudiante_id, fecha),
  CONSTRAINT ck_asistencia_estado CHECK (estado IN ('PRESENTE', 'AUSENTE', 'TARDE', 'JUSTIFICADO'))
);

-- Bloqueo en BD: no permitir fechas futuras
CREATE OR REPLACE FUNCTION fn_asistencias_no_futuro()
RETURNS TRIGGER AS $$
BEGIN
  IF NEW.fecha > CURRENT_DATE THEN
    RAISE EXCEPTION 'No se permite marcar asistencia en fechas futuras.';
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_asistencias_no_futuro_ins
BEFORE INSERT ON asistencias
FOR EACH ROW
EXECUTE FUNCTION fn_asistencias_no_futuro();

CREATE TRIGGER trg_asistencias_no_futuro_upd
BEFORE UPDATE ON asistencias
FOR EACH ROW
EXECUTE FUNCTION fn_asistencias_no_futuro();

-- Roles
INSERT INTO roles (nombre) VALUES ('admin'), ('profesor'), ('estudiante');

-- Usuarios: admin y profesores con password (DEMO: texto plano, luego lo hasheas en produccion)
INSERT INTO usuarios (rol_id, nombre, email, password_hash) VALUES
((SELECT id FROM roles WHERE nombre = 'admin'), 'Administrador Principal', 'admin@colegio.com', 'admin123'),

((SELECT id FROM roles WHERE nombre = 'profesor'), 'Profesor 1', 'prof1@colegio.com', 'prof123'),
((SELECT id FROM roles WHERE nombre = 'profesor'), 'Profesor 2', 'prof2@colegio.com', 'prof123'),
((SELECT id FROM roles WHERE nombre = 'profesor'), 'Profesor 3', 'prof3@colegio.com', 'prof123'),
((SELECT id FROM roles WHERE nombre = 'profesor'), 'Profesor 4', 'prof4@colegio.com', 'prof123'),
((SELECT id FROM roles WHERE nombre = 'profesor'), 'Profesor 5', 'prof5@colegio.com', 'prof123');

-- 25 estudiantes sin login por ahora
INSERT INTO usuarios (rol_id, nombre, email, password_hash) VALUES
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 1-1', 'e11@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 1-2', 'e12@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 1-3', 'e13@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 1-4', 'e14@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 1-5', 'e15@colegio.com', NULL),

((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 2-1', 'e21@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 2-2', 'e22@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 2-3', 'e23@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 2-4', 'e24@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 2-5', 'e25@colegio.com', NULL),

((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 3-1', 'e31@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 3-2', 'e32@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 3-3', 'e33@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 3-4', 'e34@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 3-5', 'e35@colegio.com', NULL),

((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 4-1', 'e41@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 4-2', 'e42@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 4-3', 'e43@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 4-4', 'e44@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 4-5', 'e45@colegio.com', NULL),

((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 5-1', 'e51@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 5-2', 'e52@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 5-3', 'e53@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 5-4', 'e54@colegio.com', NULL),
((SELECT id FROM roles WHERE nombre = 'estudiante'), 'Estudiante 5-5', 'e55@colegio.com', NULL);

-- 5 cursos con sus profesores
INSERT INTO cursos (nombre, profesor_id) VALUES
('Curso 1 - Matematicas', (SELECT id FROM usuarios WHERE email = 'prof1@colegio.com')),
('Curso 2 - Lenguaje',    (SELECT id FROM usuarios WHERE email = 'prof2@colegio.com')),
('Curso 3 - Historia',    (SELECT id FROM usuarios WHERE email = 'prof3@colegio.com')),
('Curso 4 - Fisica',      (SELECT id FROM usuarios WHERE email = 'prof4@colegio.com')),
('Curso 5 - Programacion',(SELECT id FROM usuarios WHERE email = 'prof5@colegio.com'));

-- Matriculas (5 estudiantes distintos por curso)
INSERT INTO matriculas (curso_id, estudiante_id)
SELECT c.id, u.id FROM cursos c CROSS JOIN usuarios u
WHERE c.nombre = 'Curso 1 - Matematicas' AND u.email IN ('e11@colegio.com', 'e12@colegio.com', 'e13@colegio.com', 'e14@colegio.com', 'e15@colegio.com');

INSERT INTO matriculas (curso_id, estudiante_id)
SELECT c.id, u.id FROM cursos c CROSS JOIN usuarios u
WHERE c.nombre = 'Curso 2 - Lenguaje' AND u.email IN ('e21@colegio.com', 'e22@colegio.com', 'e23@colegio.com', 'e24@colegio.com', 'e25@colegio.com');

INSERT INTO matriculas (curso_id, estudiante_id)
SELECT c.id, u.id FROM cursos c CROSS JOIN usuarios u
WHERE c.nombre = 'Curso 3 - Historia' AND u.email IN ('e31@colegio.com', 'e32@colegio.com', 'e33@colegio.com', 'e34@colegio.com', 'e35@colegio.com');

INSERT INTO matriculas (curso_id, estudiante_id)
SELECT c.id, u.id FROM cursos c CROSS JOIN usuarios u
WHERE c.nombre = 'Curso 4 - Fisica' AND u.email IN ('e41@colegio.com', 'e42@colegio.com', 'e43@colegio.com', 'e44@colegio.com', 'e45@colegio.com');

INSERT INTO matriculas (curso_id, estudiante_id)
SELECT c.id, u.id FROM cursos c CROSS JOIN usuarios u
WHERE c.nombre = 'Curso 5 - Programacion' AND u.email IN ('e51@colegio.com', 'e52@colegio.com', 'e53@colegio.com', 'e54@colegio.com', 'e55@colegio.com');
