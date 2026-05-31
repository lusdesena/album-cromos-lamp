-- Add admin role to the existing users/groups table.

ALTER TABLE groups
  MODIFY role ENUM('profe', 'group', 'admin') NOT NULL;
