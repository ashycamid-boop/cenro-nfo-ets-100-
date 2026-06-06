-- Fix equipment.id so new rows get valid IDs (instead of 0).
-- Safe for existing rows: assigns stable unique IDs first, then enables AUTO_INCREMENT.

ALTER TABLE equipment
  ADD COLUMN __tmp_ai BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE FIRST;

UPDATE equipment
SET id = __tmp_ai;

ALTER TABLE equipment
  DROP COLUMN __tmp_ai;

ALTER TABLE equipment
  MODIFY COLUMN id INT(11) NOT NULL,
  ADD PRIMARY KEY (id);

ALTER TABLE equipment
  MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT;
