# Expense Tracker API
A full-featured RESTful API for tracking expenses, built with Symfony 7.2.
## Overview
This expense tracking API allows users to manage expenses and categorize them. It provides endpoints for creating, reading, updating, and deleting expenses and categories, as well as obtaining expense statistics.
## Features
- 🔄 Full CRUD operations for expenses and categories
- 📊 Statistics endpoint with total expenses and breakdown by category
- 🏷️ Categorization of expenses
- 💲 Support for different payment methods
- 📅 Date-based expense tracking

## Tech Stack
- **PHP 8.2+**
- **Symfony 7.2**
- **Doctrine ORM 3.3**
- **PostgreSQL** (can be easily switched to other databases)
- **PHPUnit** for testing
- **Docker**

## API Endpoints
### Expenses
- `GET /api/expenses` - List all expenses
- `GET /api/expenses/{id}` - Get a specific expense
- `POST /api/expenses` - Create a new expense
- `PUT /api/expenses/{id}` - Update an expense
- `DELETE /api/expenses/{id}` - Delete an expense
- `GET /api/expenses/stats` - Get expense statistics

### Categories
- `GET /api/categories` - List all categories
- `GET /api/categories/{id}` - Get a specific category
- `POST /api/categories` - Create a new category
- `PUT /api/categories/{id}` - Update a category
- `DELETE /api/categories/{id}` - Delete a category

## Installation
### Prerequisites
- PHP 8.2 or higher
- Composer
- PostgreSQL

### Steps
1. **Clone the repository**
``` bash
   git clone https://github.com/pusachev/expense-tracker-api.git
   cd expense-tracker-api
```
1. **Install dependencies**
``` bash
   composer install
```
1. **Set up the database**
``` bash
   # Create database schema
   php bin/console doctrine:schema:create
   
   # Load fixture data (optional)
   php bin/console doctrine:fixtures:load
```
1. **Start the development server**
``` bash
   symfony server:start
```
## Usage
### Creating an Expense
``` bash
curl -X POST -H "Content-Type: application/json" -d '{
  "description": "Grocery shopping",
  "amount": 150.50,
  "date": "2023-07-15T10:00:00",
  "category_id": 1,
  "payment_method": "Card"
}' http://localhost:8000/api/expenses
```
### Getting All Expenses
``` bash
curl http://localhost:8000/api/expenses
```
### Getting Expense Statistics
``` bash
curl http://localhost:8000/api/expenses/stats
```
## Data Models
### Expense
- `id` - Auto-incremented ID
- `description` - Text description of the expense
- - Decimal amount `amount`
- `date` - Date and time of the expense
- - Payment method used (optional) `payment_method`
- `category` - Associated expense category (optional)

### Category
- `id` - Auto-incremented ID
- `name` - Category name
- - Collection of associated expenses `expenses`

## Testing
Run the test suite with:
``` bash
php bin/phpunit
```
The project includes comprehensive tests for all API endpoints.
## Docker Support
You can run this project using Docker:
``` bash
# Start Docker containers
docker-compose up -d

# Initialize database
docker-compose exec php php bin/console doctrine:schema:create
docker-compose exec php php bin/console doctrine:fixtures:load
```
## Contributing
Contributions are welcome! Please feel free to submit a Pull Request.
1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License
This project is licensed under the MIT License - see the LICENSE file for details.
## Acknowledgements
- [Symfony](https://symfony.com/) - The PHP framework used
- [Doctrine](https://www.doctrine-project.org/) - ORM used for database operations
- [PHPUnit](https://phpunit.de/) - Testing framework
