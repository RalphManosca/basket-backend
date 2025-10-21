# Cinch E-commerce Backend

This is a microservices-based e-commerce backend built with Laravel. The system is split into three independent services that handle products, orders, and email notifications.

## How It Works

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  Catalog Service│     │ Checkout Service │     │  Email Service  │
│    (Port 8001)  │     │   (Port 8002)    │     │   (Port 8003)   │
└────────┬────────┘     └─────────┬────────┘     └────────┬────────┘
         │                        │                       │
         │                        │                       │
         └────────────────────────┼───────────────────────┘
                                  │
                          ┌───────▼────────┐
                          │  MySQL 8.0     │
                          │  (Port 3306)   │
                          └────────────────┘
```

## The Three Services

### Catalog Service (Port 8001)
Handles all product-related operations. You can fetch the product list or get details about a specific product.
- `GET /api/products` - Get all products
- `GET /api/products/{id}` - Get a single product

### Checkout Service (Port 8002)
Takes care of order processing. When a customer places an order, this service creates it and stores it in the database.
- `POST /api/orders` - Create a new order
- `GET /api/orders/{id}` - View an order

### Email Service (Port 8003)
Sends order confirmation emails to customers. For local development, emails go to MailHog instead of real inboxes (you can view them at http://localhost:8025).
- `POST /api/email/send-order` - Send order confirmation

## What You'll Need

**For local development:**
- Docker Desktop (or Docker Engine + Docker Compose)
- Git

**For AWS deployment:**
- AWS CLI and SAM CLI installed
- An AWS account with EC2 access
- An EC2 key pair set up

## Getting Started

### Setup

First, clone the repo and navigate to the project folder:

```bash
git clone <your-repo-url>
cd basket-backend
```

Then build and start everything with Docker:

```bash
docker-compose build
docker-compose up -d
```

That's it! The services will automatically set up databases, run migrations, and add 12 sample products.

### Check That It's Working

Once everything's running, you can access:
- Products API: http://localhost:8001/api/products
- Orders API: http://localhost:8002/api/orders
- Email API: http://localhost:8003/api/email/send-order
- MailHog (email viewer): http://localhost:8025

### Try It Out

**Fetch the product catalog:**
```bash
curl http://localhost:8001/api/products
```

**Place an order:**
```bash
curl -X POST http://localhost:8002/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "user_email": "customer@example.com",
    "items": [
      {
        "product_id": 1,
        "product_name": "Wireless Bluetooth Headphones",
        "quantity": 2,
        "unit_price": 129.99
      }
    ]
  }'
```

**Send a confirmation email:**
```bash
curl -X POST http://localhost:8003/api/email/send-order \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": 1,
    "user_email": "customer@example.com",
    "order_details": {
      "id": 1,
      "total_amount": 259.98,
      "items": [
        {
          "product_name": "Wireless Bluetooth Headphones",
          "quantity": 2,
          "unit_price": 129.99
        }
      ]
    }
  }'
```

Then open http://localhost:8025 to see the email in MailHog.

## Database Structure

Each service has its own database. Here's what they store:

### Catalog Database

**products**
| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| name | VARCHAR(255) | Product name |
| description | TEXT | Product description |
| price | DECIMAL(10,2) | Product price |
| stock | INT | Stock quantity |
| image_url | VARCHAR(500) | Product image URL |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |

### Checkout Database

**orders**
| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| user_email | VARCHAR(255) | Customer email |
| total_amount | DECIMAL(10,2) | Order total |
| status | VARCHAR(50) | Order status |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |

**order_items**
| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| order_id | BIGINT | Foreign key to orders |
| product_id | BIGINT | Product reference |
| product_name | VARCHAR(255) | Product name snapshot |
| quantity | INT | Quantity ordered |
| unit_price | DECIMAL(10,2) | Price snapshot |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Update timestamp |

### Email Database

**email_logs**
| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| order_id | BIGINT | Order reference |
| recipient | VARCHAR(255) | Email recipient |
| status | VARCHAR(50) | Send status |
| error_message | TEXT | Error details (if any) |
| sent_at | TIMESTAMP | Send timestamp |

## API Reference

### Catalog Service

**List all products**
```http
GET /api/products
```

Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Wireless Bluetooth Headphones",
      "description": "Premium noise-cancelling wireless headphones",
      "price": 129.99,
      "stock": 50,
      "image_url": "https://images.unsplash.com/...",
      "created_at": "2025-01-15T10:30:00Z"
    }
  ]
}
```

**Get a specific product**
```http
GET /api/products/{id}
```

Response:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Wireless Bluetooth Headphones",
    "description": "Premium noise-cancelling wireless headphones",
    "price": 129.99,
    "stock": 50,
    "image_url": "https://images.unsplash.com/..."
  }
}
```

### Checkout Service

**Create a new order**
```http
POST /api/orders
Content-Type: application/json
```

Request:
```json
{
  "user_email": "customer@example.com",
  "items": [
    {
      "product_id": 1,
      "product_name": "Product Name",
      "quantity": 2,
      "unit_price": 29.99
    }
  ]
}
```

Response:
```json
{
  "success": true,
  "data": {
    "id": 123,
    "user_email": "customer@example.com",
    "total_amount": 59.98,
    "status": "completed",
    "items": [...],
    "created_at": "2025-01-15T10:30:00Z"
  }
}
```

**Get order details**
```http
GET /api/orders/{id}
```

Response:
```json
{
  "success": true,
  "data": {
    "id": 123,
    "user_email": "customer@example.com",
    "total_amount": 59.98,
    "status": "completed",
    "items": [...],
    "created_at": "2025-01-15T10:30:00Z"
  }
}
```

### Email Service

**Send order confirmation**
```http
POST /api/email/send-order
Content-Type: application/json
```

Request:
```json
{
  "order_id": 123,
  "user_email": "customer@example.com",
  "order_details": {
    "id": 123,
    "total_amount": 59.98,
    "items": [
      {
        "product_name": "Product Name",
        "quantity": 2,
        "unit_price": 29.99
      }
    ]
  }
}
```

Response:
```json
{
  "success": true,
  "message": "Email sent successfully"
}
```

## Deploying to AWS

### Quick Deploy with SAM

The easiest way to deploy is using AWS SAM:

```bash
# Install SAM CLI (macOS)
brew install aws-sam-cli

# Configure your AWS credentials
aws configure

# Validate and deploy
sam validate -t template.yaml
sam deploy --guided
```

During deployment, you'll be asked for:
- Stack name (e.g., `cinch-backend`)
- AWS Region (e.g., `us-east-1`)
- Your EC2 key pair name
- A secure database password

**Note:** The template includes placeholder AMI IDs. Find the right one for your region with:

```bash
aws ec2 describe-images \
  --owners amazon \
  --filters "Name=name,Values=amzn2-ami-hvm-*-x86_64-gp2" \
  --query 'sort_by(Images, &CreationDate)[-1].ImageId' \
  --output text
```

After the infrastructure is up, build and push your Docker images, then SSH into the EC2 instances to pull and run them.

### Manual Deployment

If you prefer setting things up yourself:

1. Create an RDS MySQL instance (MySQL 8.0, db.t3.micro)
2. Launch three t2.micro EC2 instances running Amazon Linux 2
3. Set up security groups to allow HTTP and SSH
4. Configure AWS SES for sending emails in production
5. Install Docker on each instance and run the service containers

## Environment Variables

### Catalog Service
```env
APP_NAME="Catalog Service"
APP_URL=http://localhost:8001
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=catalog_db
DB_USERNAME=root
DB_PASSWORD=secret
```

### Checkout Service
```env
APP_NAME="Checkout Service"
APP_URL=http://localhost:8002
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=checkout_db
DB_USERNAME=root
DB_PASSWORD=secret
EMAIL_SERVICE_URL=http://email-service
```

### Email Service
```env
APP_NAME="Email Service"
APP_URL=http://localhost:8003
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=email_db
DB_USERNAME=root
DB_PASSWORD=secret
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_FROM_ADDRESS=noreply@cinch.com
MAIL_FROM_NAME="Cinch Store"
```

## Testing

You can test the full flow with curl (or use Postman if you prefer):

```bash
# 1. Get products
curl http://localhost:8001/api/products

# 2. Create an order
curl -X POST http://localhost:8002/api/orders \
  -H "Content-Type: application/json" \
  -d '{"user_email":"test@example.com","items":[{"product_id":1,"product_name":"Test Product","quantity":1,"unit_price":29.99}]}'

# 3. Send confirmation email
curl -X POST http://localhost:8003/api/email/send-order \
  -H "Content-Type: application/json" \
  -d '{"order_id":1,"user_email":"test@example.com","order_details":{"id":1,"total_amount":29.99,"items":[{"product_name":"Test Product","quantity":1,"unit_price":29.99}]}}'

# 4. Check the email at http://localhost:8025
```

## Development Tips

**Adding products manually:**

```bash
# SSH into catalog-service container
docker exec -it cinch-catalog-service bash

# Run tinker
php artisan tinker

# Create product
Product::create([
    'name' => 'New Product',
    'description' => 'Description',
    'price' => 99.99,
    'stock' => 100,
    'image_url' => 'https://example.com/image.jpg'
]);
```

**Viewing logs:**

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f catalog-service
docker-compose logs -f checkout-service
docker-compose logs -f email-service
```

**Accessing the database:**

```bash
# Connect to MySQL
docker exec -it cinch-mysql mysql -uroot -psecret

# Select database
USE catalog_db;

# Query
SELECT * FROM products;
```

**Rebuilding after code changes:**

```bash
# Rebuild specific service
docker-compose build catalog-service
docker-compose up -d catalog-service

# Rebuild all
docker-compose down
docker-compose build
docker-compose up -d
```

## Troubleshooting

**Services won't start?**
```bash
# Check logs
docker-compose logs

# Verify MySQL is healthy
docker-compose ps mysql

# Restart services
docker-compose restart
```

**Getting database errors?**
```bash
# Wait for MySQL to be ready (takes ~10-15 seconds)
docker-compose logs mysql

# Manually run migrations
docker exec -it cinch-catalog-service php artisan migrate
```

**Emails not sending?**
```bash
# Check MailHog is running
docker-compose ps mailhog

# Check email service logs
docker-compose logs email-service

# Verify MAIL_HOST in .env
docker exec -it cinch-email-service cat .env | grep MAIL_HOST
```

---

Built for the Cinch coding assignment.
