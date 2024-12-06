# Online Laundry Management System

## Project Overview
Develop a comprehensive web-based laundry management system that allows customers to book laundry services and enables administrators to manage orders, services, and user accounts.

## Technical Stack
- Frontend: HTML5, CSS3, Vanilla JavaScript
- Backend: Pure PHP (No Frameworks)
- Database: MySQL
- Server: Apache
- Version Control: Git

## Database Schema

### Users Table
```sql
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);
```

### Services Table
```sql
CREATE TABLE services (
    service_id INT PRIMARY KEY AUTO_INCREMENT,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    price_per_kg DECIMAL(10,2) NOT NULL,
    service_type ENUM('wash', 'dry_clean', 'iron', 'special') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active'
);
```

### Orders Table
```sql
CREATE TABLE orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    total_weight DECIMAL(10,2),
    total_price DECIMAL(10,2),
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pickup_date DATE,
    delivery_date DATE,
    status ENUM('pending', 'processing', 'ready', 'delivered', 'cancelled') DEFAULT 'pending',
    special_instructions TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```

### Order_Items Table
```sql
CREATE TABLE order_items (
    order_item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    service_id INT,
    quantity DECIMAL(10,2),
    item_price DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (service_id) REFERENCES services(service_id)
);
```

## Features

### Customer Features
1. User Registration and Login
   - Secure registration with email verification
   - Password reset functionality
   - Social login (optional)

2. Service Booking
   - Browse available laundry services
   - Select multiple services
   - Calculate total price based on weight and service type
   - Schedule pickup and delivery dates
   - Add special instructions

3. Order Management
   - View order history
   - Track order status
   - Cancel pending orders
   - Download invoice/receipt

4. Profile Management
   - Update personal information
   - Change password
   - View and manage addresses

### Admin Features
1. Dashboard
   - Overview of daily/weekly orders
   - Revenue analytics
   - Pending order count

2. User Management
   - View all registered users
   - Block/unblock user accounts
   - View user order history

3. Service Management
   - Add, edit, and remove laundry services
   - Set pricing
   - Manage service availability

4. Order Management
   - View all orders
   - Update order status
   - Assign pickup and delivery personnel
   - Generate reports

## Security Considerations
- Implement prepared statements to prevent SQL injection
- Use password hashing (bcrypt/Argon2)
- Implement CSRF protection
- Input validation and sanitization
- Role-based access control
- HTTPS implementation

## Frontend Requirements
- Responsive design
- Mobile-first approach
- Clean and intuitive UI
- Form validation
- AJAX for dynamic content loading
- Error handling and user feedback

## Backend Architecture
- Separate configuration files
- Modular file structure
- Object-oriented programming principles
- Error logging
- Configuration management

## Recommended Project Structure
```
laundry-management-system/
│
├── config/
│   ├── database.php
│   └── config.php
│
├── includes/
│   ├── functions.php
│   └── auth.php
│
├── public/
│   ├── css/
│   ├── js/
│   └── index.php
│
├── admin/
│   ├── dashboard.php
│   ├── users.php
│   └── orders.php
│
├── customer/
│   ├── profile.php
│   ├── book-service.php
│   └── orders.php
│
└── api/
    ├── user.php
    ├── order.php
    └── service.php
```

## Additional Recommendations
- Implement email notifications for order status
- Create a comprehensive error handling mechanism
- Design a backup and restore system for database
- Consider future scalability

## Deployment Considerations
- Use environment-based configuration
- Implement proper logging
- Set up continuous integration
- Configure proper server permissions

## Testing
- Unit testing for critical functions
- Integration testing
- Security vulnerability testing
- Performance benchmarkingP