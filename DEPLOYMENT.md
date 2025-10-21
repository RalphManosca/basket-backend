# AWS Deployment Guide

This guide provides step-by-step instructions for deploying the Cinch E-commerce backend to AWS.

## Prerequisites

- AWS Account
- AWS CLI installed and configured
- AWS SAM CLI installed
- EC2 Key Pair created
- Docker installed locally

## Deployment Options

### Option 1: Using AWS SAM (Recommended)

#### Step 1: Install AWS SAM CLI

```bash
# macOS
brew install aws-sam-cli

# Linux
wget https://github.com/aws/aws-sam-cli/releases/latest/download/aws-sam-cli-linux-x86_64.zip
unzip aws-sam-cli-linux-x86_64.zip -d sam-installation
sudo ./sam-installation/install

# Windows (PowerShell)
choco install aws-sam-cli
```

#### Step 2: Configure AWS Credentials

```bash
aws configure
# Enter:
# - AWS Access Key ID
# - AWS Secret Access Key
# - Default region (e.g., us-east-1)
# - Default output format (json)
```

#### Step 3: Create EC2 Key Pair

```bash
# Create new key pair
aws ec2 create-key-pair \
    --key-name cinch-backend-key \
    --query 'KeyMaterial' \
    --output text > cinch-backend-key.pem

# Set permissions
chmod 400 cinch-backend-key.pem
```

#### Step 4: Update AMI IDs in template.yaml

Find the latest Amazon Linux 2 AMI for your region:

```bash
aws ec2 describe-images \
    --owners amazon \
    --filters "Name=name,Values=amzn2-ami-hvm-*-x86_64-gp2" \
    --query 'sort_by(Images, &CreationDate)[-1].[ImageId,Name,CreationDate]' \
    --output table
```

Update the `ImageId` in `template.yaml` for all EC2 instances.

#### Step 5: Validate Template

```bash
sam validate -t template.yaml
```

#### Step 6: Deploy Stack

```bash
sam deploy \
    --template-file template.yaml \
    --stack-name cinch-backend \
    --parameter-overrides \
        KeyName=cinch-backend-key \
        DBPassword=YourSecurePassword123! \
        EnvironmentType=production \
    --capabilities CAPABILITY_IAM \
    --region us-east-1
```

#### Step 7: Get Stack Outputs

```bash
aws cloudformation describe-stacks \
    --stack-name cinch-backend \
    --query 'Stacks[0].Outputs' \
    --output table
```

This will show:
- Catalog Service URL
- Checkout Service URL
- Email Service URL
- RDS Endpoint

---

### Option 2: Manual Deployment

#### Step 1: Create RDS MySQL Instance

```bash
# Create DB Subnet Group
aws rds create-db-subnet-group \
    --db-subnet-group-name cinch-db-subnet \
    --db-subnet-group-description "Cinch DB Subnet Group" \
    --subnet-ids subnet-xxxxx subnet-yyyyy

# Create Security Group
aws ec2 create-security-group \
    --group-name cinch-db-sg \
    --description "Cinch Database Security Group" \
    --vpc-id vpc-xxxxx

# Allow MySQL from your IP
aws ec2 authorize-security-group-ingress \
    --group-id sg-xxxxx \
    --protocol tcp \
    --port 3306 \
    --cidr YOUR_IP/32

# Create RDS Instance
aws rds create-db-instance \
    --db-instance-identifier cinch-mysql \
    --db-instance-class db.t3.micro \
    --engine mysql \
    --engine-version 8.0.35 \
    --master-username admin \
    --master-user-password YourSecurePassword \
    --allocated-storage 20 \
    --vpc-security-group-ids sg-xxxxx \
    --db-subnet-group-name cinch-db-subnet \
    --backup-retention-period 7 \
    --no-publicly-accessible
```

Wait for instance to be available:

```bash
aws rds wait db-instance-available --db-instance-identifier cinch-mysql
```

Get RDS endpoint:

```bash
aws rds describe-db-instances \
    --db-instance-identifier cinch-mysql \
    --query 'DBInstances[0].Endpoint.Address' \
    --output text
```

#### Step 2: Create Databases

Connect to RDS and create databases:

```bash
mysql -h your-rds-endpoint.rds.amazonaws.com -u admin -p

CREATE DATABASE catalog_db;
CREATE DATABASE checkout_db;
CREATE DATABASE email_db;
exit;
```

#### Step 3: Build and Push Docker Images

##### Option A: Amazon ECR

```bash
# Create ECR repositories
aws ecr create-repository --repository-name cinch/catalog-service
aws ecr create-repository --repository-name cinch/checkout-service
aws ecr create-repository --repository-name cinch/email-service

# Get ECR login
aws ecr get-login-password --region us-east-1 | \
    docker login --username AWS --password-stdin \
    YOUR_ACCOUNT_ID.dkr.ecr.us-east-1.amazonaws.com

# Build and tag images
docker build -t cinch/catalog-service ./catalog-service
docker tag cinch/catalog-service:latest \
    YOUR_ACCOUNT_ID.dkr.ecr.us-east-1.amazonaws.com/cinch/catalog-service:latest

docker build -t cinch/checkout-service ./checkout-service
docker tag cinch/checkout-service:latest \
    YOUR_ACCOUNT_ID.dkr.ecr.us-east-1.amazonaws.com/cinch/checkout-service:latest

docker build -t cinch/email-service ./email-service
docker tag cinch/email-service:latest \
    YOUR_ACCOUNT_ID.dkr.ecr.us-east-1.amazonaws.com/cinch/email-service:latest

# Push images
docker push YOUR_ACCOUNT_ID.dkr.ecr.us-east-1.amazonaws.com/cinch/catalog-service:latest
docker push YOUR_ACCOUNT_ID.dkr.ecr.us-east-1.amazonaws.com/cinch/checkout-service:latest
docker push YOUR_ACCOUNT_ID.dkr.ecr.us-east-1.amazonaws.com/cinch/email-service:latest
```

##### Option B: Docker Hub

```bash
# Login to Docker Hub
docker login

# Build and push
docker build -t your-username/cinch-catalog:latest ./catalog-service
docker push your-username/cinch-catalog:latest

docker build -t your-username/cinch-checkout:latest ./checkout-service
docker push your-username/cinch-checkout:latest

docker build -t your-username/cinch-email:latest ./email-service
docker push your-username/cinch-email:latest
```

#### Step 4: Launch EC2 Instances

```bash
# Create security group
aws ec2 create-security-group \
    --group-name cinch-web-sg \
    --description "Cinch Web Security Group" \
    --vpc-id vpc-xxxxx

# Allow HTTP and SSH
aws ec2 authorize-security-group-ingress \
    --group-id sg-xxxxx \
    --protocol tcp \
    --port 80 \
    --cidr 0.0.0.0/0

aws ec2 authorize-security-group-ingress \
    --group-id sg-xxxxx \
    --protocol tcp \
    --port 22 \
    --cidr YOUR_IP/32

# Launch instances (repeat for each service)
aws ec2 run-instances \
    --image-id ami-xxxxx \
    --instance-type t2.micro \
    --key-name cinch-backend-key \
    --security-group-ids sg-xxxxx \
    --subnet-id subnet-xxxxx \
    --tag-specifications 'ResourceType=instance,Tags=[{Key=Name,Value=cinch-catalog-service}]' \
    --user-data file://user-data-catalog.sh
```

#### Step 5: Configure EC2 Instances

Create `user-data-catalog.sh`:

```bash
#!/bin/bash
yum update -y
amazon-linux-extras install docker -y
service docker start
usermod -a -G docker ec2-user

# Pull and run catalog service
docker pull YOUR_ACCOUNT_ID.dkr.ecr.us-east-1.amazonaws.com/cinch/catalog-service:latest
docker run -d -p 80:80 \
    -e DB_HOST=your-rds-endpoint.rds.amazonaws.com \
    -e DB_PASSWORD=YourPassword \
    -e DB_DATABASE=catalog_db \
    YOUR_ACCOUNT_ID.dkr.ecr.us-east-1.amazonaws.com/cinch/catalog-service:latest
```

#### Step 6: Configure SES for Emails

```bash
# Verify email address
aws ses verify-email-identity --email-address noreply@yourdomain.com

# Check verification status
aws ses get-identity-verification-attributes \
    --identities noreply@yourdomain.com

# Move out of sandbox (submit request in AWS Console)
# https://console.aws.amazon.com/ses/home#/account
```

---

## Post-Deployment Steps

### 1. Run Migrations

SSH into each EC2 instance:

```bash
ssh -i cinch-backend-key.pem ec2-user@INSTANCE_PUBLIC_IP

# Run migrations
docker exec -it CONTAINER_ID php artisan migrate --force
```

### 2. Seed Catalog Database

```bash
# SSH into catalog service
ssh -i cinch-backend-key.pem ec2-user@CATALOG_INSTANCE_IP

# Seed products
docker exec -it CONTAINER_ID php artisan db:seed --class=ProductSeeder --force
```

### 3. Test Endpoints

```bash
# Catalog Service
curl http://CATALOG_PUBLIC_IP/api/products

# Checkout Service
curl -X POST http://CHECKOUT_PUBLIC_IP/api/orders \
    -H "Content-Type: application/json" \
    -d '{"user_email":"test@example.com","items":[...]}'

# Email Service
curl -X POST http://EMAIL_PUBLIC_IP/api/email/send-order \
    -H "Content-Type: application/json" \
    -d '{"order_id":1,"user_email":"test@example.com",...}'
```

### 4. Configure Domain (Optional)

```bash
# Create Route 53 hosted zone
aws route53 create-hosted-zone --name yourdomain.com --caller-reference $(date +%s)

# Create A records pointing to EC2 public IPs
# catalog.yourdomain.com -> Catalog EC2 IP
# checkout.yourdomain.com -> Checkout EC2 IP
# email.yourdomain.com -> Email EC2 IP
```

### 5. Setup SSL/TLS (Optional but Recommended)

Use AWS Certificate Manager (ACM) and Application Load Balancer (ALB):

```bash
# Request certificate
aws acm request-certificate \
    --domain-name yourdomain.com \
    --subject-alternative-names *.yourdomain.com \
    --validation-method DNS

# Create ALB
aws elbv2 create-load-balancer \
    --name cinch-alb \
    --subnets subnet-xxxxx subnet-yyyyy \
    --security-groups sg-xxxxx

# Create target groups for each service
# Register EC2 instances with target groups
# Create listeners with SSL certificate
```

---

## Monitoring and Logging

### CloudWatch Logs

Enable CloudWatch agent on EC2 instances:

```bash
# Install CloudWatch agent
wget https://s3.amazonaws.com/amazoncloudwatch-agent/amazon_linux/amd64/latest/amazon-cloudwatch-agent.rpm
sudo rpm -U ./amazon-cloudwatch-agent.rpm

# Configure agent
sudo /opt/aws/amazon-cloudwatch-agent/bin/amazon-cloudwatch-agent-config-wizard

# Start agent
sudo /opt/aws/amazon-cloudwatch-agent/bin/amazon-cloudwatch-agent-ctl \
    -a fetch-config \
    -m ec2 \
    -s -c file:/opt/aws/amazon-cloudwatch-agent/etc/amazon-cloudwatch-agent.json
```

### CloudWatch Alarms

```bash
# CPU alarm
aws cloudwatch put-metric-alarm \
    --alarm-name cinch-catalog-high-cpu \
    --alarm-description "Alert when CPU exceeds 80%" \
    --metric-name CPUUtilization \
    --namespace AWS/EC2 \
    --statistic Average \
    --period 300 \
    --threshold 80 \
    --comparison-operator GreaterThanThreshold \
    --dimensions Name=InstanceId,Value=i-xxxxx \
    --evaluation-periods 2
```

---

## Cost Optimization

### AWS Free Tier Eligible Resources

- **EC2**: t2.micro (750 hours/month for 12 months)
- **RDS**: db.t3.micro (750 hours/month for 12 months)
- **Data Transfer**: 15 GB/month outbound

### Estimated Monthly Costs (After Free Tier)

- EC2 instances (3 × t2.micro): ~$7/month each = **$21/month**
- RDS (db.t3.micro): ~**$15/month**
- EBS storage (60 GB): ~**$6/month**
- Data transfer: ~**$10/month**

**Total**: ~**$52/month** (excluding free tier)

### Cost Reduction Tips

1. Use Reserved Instances for production (save up to 72%)
2. Stop non-production instances when not in use
3. Use S3 for static assets instead of serving from EC2
4. Enable RDS automated backups only for production
5. Use CloudFront CDN to reduce data transfer costs

---

## Backup and Disaster Recovery

### RDS Automated Backups

```bash
# Modify RDS to enable automated backups
aws rds modify-db-instance \
    --db-instance-identifier cinch-mysql \
    --backup-retention-period 7 \
    --preferred-backup-window "03:00-04:00" \
    --apply-immediately
```

### Manual Snapshots

```bash
# Create snapshot
aws rds create-db-snapshot \
    --db-instance-identifier cinch-mysql \
    --db-snapshot-identifier cinch-mysql-snapshot-$(date +%Y%m%d)

# List snapshots
aws rds describe-db-snapshots \
    --db-instance-identifier cinch-mysql
```

### EC2 AMI Backups

```bash
# Create AMI from running instance
aws ec2 create-image \
    --instance-id i-xxxxx \
    --name "cinch-catalog-$(date +%Y%m%d)" \
    --description "Catalog service backup"
```

---

## Rollback Procedures

### Rollback Docker Image

```bash
# SSH into EC2
ssh -i cinch-backend-key.pem ec2-user@INSTANCE_IP

# Stop current container
docker stop CONTAINER_ID

# Pull previous version
docker pull YOUR_REGISTRY/service:previous-tag

# Run previous version
docker run -d -p 80:80 YOUR_REGISTRY/service:previous-tag
```

### Rollback Database Migration

```bash
# SSH into service
ssh -i cinch-backend-key.pem ec2-user@INSTANCE_IP

# Rollback migration
docker exec -it CONTAINER_ID php artisan migrate:rollback --step=1
```

---

## Security Checklist

- [ ] Enable VPC for network isolation
- [ ] Use private subnets for RDS
- [ ] Configure security groups with least privilege
- [ ] Enable RDS encryption at rest
- [ ] Use AWS Secrets Manager for credentials
- [ ] Enable CloudTrail for audit logging
- [ ] Configure WAF rules if using ALB
- [ ] Implement rate limiting
- [ ] Enable HTTPS/SSL with ACM
- [ ] Regular security patching of EC2 instances
- [ ] Enable MFA on AWS root account
- [ ] Use IAM roles instead of access keys

---

## Cleanup (Destroy Infrastructure)

### SAM Deployment

```bash
sam delete --stack-name cinch-backend
```

### Manual Deployment

```bash
# Terminate EC2 instances
aws ec2 terminate-instances --instance-ids i-xxxxx i-yyyyy i-zzzzz

# Delete RDS instance
aws rds delete-db-instance \
    --db-instance-identifier cinch-mysql \
    --skip-final-snapshot

# Delete security groups
aws ec2 delete-security-group --group-id sg-xxxxx

# Delete ECR repositories
aws ecr delete-repository --repository-name cinch/catalog-service --force
aws ecr delete-repository --repository-name cinch/checkout-service --force
aws ecr delete-repository --repository-name cinch/email-service --force
```

---

## Troubleshooting

### Can't connect to RDS from EC2

1. Check security group allows traffic from EC2 security group
2. Verify RDS is in same VPC as EC2
3. Check RDS endpoint is correct
4. Test connection: `mysql -h RDS_ENDPOINT -u admin -p`

### Docker container won't start

1. Check logs: `docker logs CONTAINER_ID`
2. Verify environment variables
3. Check database connectivity
4. Ensure migrations ran successfully

### SES emails not sending

1. Verify email address in SES console
2. Check if still in sandbox mode (limits to verified addresses)
3. Request production access if needed
4. Check IAM permissions for EC2 instance role

---

## Support

For AWS-specific issues, consult:
- [AWS Documentation](https://docs.aws.amazon.com)
- [AWS Support](https://console.aws.amazon.com/support)
- [AWS Forums](https://forums.aws.amazon.com)
