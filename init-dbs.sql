-- Create databases for each microservice
CREATE DATABASE IF NOT EXISTS catalog_db;
CREATE DATABASE IF NOT EXISTS checkout_db;
CREATE DATABASE IF NOT EXISTS email_db;

-- Grant privileges (optional, since we're using root)
GRANT ALL PRIVILEGES ON catalog_db.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON checkout_db.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON email_db.* TO 'root'@'%';

FLUSH PRIVILEGES;
