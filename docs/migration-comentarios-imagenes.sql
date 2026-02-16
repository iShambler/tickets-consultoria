-- Migración: Soporte de imágenes en comentarios
-- Añade comentario_id a ticket_archivos para vincular adjuntos a comentarios específicos

ALTER TABLE ticket_archivos 
ADD COLUMN comentario_id INT UNSIGNED NULL AFTER usuario_id,
ADD FOREIGN KEY (comentario_id) REFERENCES ticket_comentarios(id) ON DELETE SET NULL,
ADD INDEX idx_comentario (comentario_id);
