USE medihub_db;


-- Run this if your existing medihub_db was already imported.


ALTER TABLE medicines
ADD COLUMN image_path VARCHAR(255) NULL
AFTER requires_prescription;


UPDATE medicines
SET image_path = 'images/paracetamol.jpg'
WHERE LOWER(medicine_name) LIKE '%paracetamol%';


UPDATE medicines
SET image_path = 'images/amoxicillin.jpeg'
WHERE LOWER(medicine_name) LIKE '%amoxicillin%'
   OR LOWER(medicine_name) LIKE '%azithromycin%';


UPDATE medicines
SET image_path = 'images/vitamin c.jpg'
WHERE LOWER(medicine_name) LIKE '%vitamin%';