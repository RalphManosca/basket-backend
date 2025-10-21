#!/bin/bash

echo "==================================="
echo "Cinch E-commerce Backend Setup"
echo "==================================="
echo ""

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker Desktop first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

echo "✅ Docker and Docker Compose are installed"
echo ""

# Copy .env.example to .env for each service if not exists
echo "📝 Setting up environment files..."

for service in catalog-service checkout-service email-service; do
    if [ ! -f "$service/.env" ]; then
        cp "$service/.env.example" "$service/.env"
        echo "  ✅ Created .env for $service"
    else
        echo "  ⏭️  .env already exists for $service"
    fi
done

echo ""
echo "🐳 Building Docker images..."
docker-compose build

echo ""
echo "🚀 Starting services..."
docker-compose up -d

echo ""
echo "⏳ Waiting for services to be ready (30 seconds)..."
sleep 30

echo ""
echo "✅ Services are running!"
echo ""
echo "📡 Service URLs:"
echo "  - Catalog Service:  http://localhost:8001/api/products"
echo "  - Checkout Service: http://localhost:8002/api/orders"
echo "  - Email Service:    http://localhost:8003/api/email/send-order"
echo "  - MailHog UI:       http://localhost:8025"
echo ""
echo "📊 View logs:"
echo "  docker-compose logs -f"
echo ""
echo "🛑 Stop services:"
echo "  docker-compose down"
echo ""
echo "Happy coding! 🎉"
