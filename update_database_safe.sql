USE medihub_db;


SET @has_image_path := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'medicines'
      AND COLUMN_NAME = 'image_path'
);


SET @sql := IF(
    @has_image_path = 0,
    'ALTER TABLE medicines ADD COLUMN image_path VARCHAR(255) NULL AFTER requires_prescription',
    'SELECT 1'
);


PREPARE stmt FROM @sql;

EXECUTE stmt;

DEALLOCATE PREPARE stmt;


UPDATE medicines
SET image_path = 'images/paracetamol.jpg'
WHERE LOWER(medicine_name) LIKE '%paracetamol%'
  AND (image_path IS NULL OR image_path = '');


UPDATE medicines
SET image_path = 'images/amoxicillin.jpeg'
WHERE (
    LOWER(medicine_name) LIKE '%amoxicillin%'
    OR LOWER(medicine_name) LIKE '%azithromycin%'
)
AND (image_path IS NULL OR image_path = '');


UPDATE medicines
SET image_path = 'images/vitamin c.jpg'
WHERE LOWER(medicine_name) LIKE '%vitamin%'
  AND (image_path IS NULL OR image_path = '');