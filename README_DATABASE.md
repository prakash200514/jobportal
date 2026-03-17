# JobHunt Database Integration

This project has been enhanced with full database connectivity using PHP and MySQL. All files are now connected to a MySQL database for dynamic content management.

## 🗄️ Database Features

### Database Tables
- **users** - User accounts (job seekers and employers)
- **job_categories** - Job categories with icons and counts
- **companies** - Company information and logos
- **jobs** - Job postings with full details
- **job_applications** - Job application tracking
- **testimonials** - User testimonials and reviews

### API Endpoints
- `api/auth.php` - User authentication (login/register/logout)
- `api/jobs.php` - Job management (CRUD operations)
- `api/applications.php` - Application management
- `api/categories.php` - Job categories
- `api/testimonials.php` - Testimonials management

## 🚀 Setup Instructions

### Prerequisites
- XAMPP/WAMP/LAMP server
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser

### Installation Steps

1. **Start your web server**
   - Start Apache and MySQL services in XAMPP/WAMP

2. **Database Configuration**
   - The database will be created automatically when you first run the application
   - Default database name: `jobhunt_db`
   - Default credentials: `root` (no password)

3. **File Setup**
   - Place all files in your web server directory (e.g., `htdocs` for XAMPP)
   - Ensure proper file permissions

4. **Initialize Database**
   - Visit `setup.php` in your browser to initialize the database
   - This will create all tables and insert sample data

5. **Access the Application**
   - Visit `index.php` to access the main application
   - The original `index.html` is still available for reference

## 🔧 Configuration

### Database Settings
Edit `config.php` to modify database settings:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jobhunt_db');
```

### File Uploads
- Resume uploads are stored in `uploads/resumes/` directory
- Make sure this directory has write permissions

## 📱 Features

### For Job Seekers
- **User Registration/Login** - Create account and manage profile
- **Job Search** - Search jobs by title, location, category, type
- **Job Applications** - Apply for jobs with resume upload
- **Application Tracking** - View application status
- **Profile Management** - Update personal information

### For Employers
- **Job Posting** - Post new job openings
- **Application Management** - Review and manage applications
- **Company Profile** - Manage company information

### General Features
- **Dynamic Content** - All content loaded from database
- **Real-time Search** - Live job search with filters
- **Responsive Design** - Works on all devices
- **Modern UI** - Clean and professional interface

## 🎯 Usage

### Getting Started
1. Visit `index.php`
2. Click "Register" to create an account
3. Browse available jobs
4. Apply for jobs of interest
5. Track your applications

### Job Search
- Use the search bar to find jobs by title
- Filter by location, job type, or category
- Click on job cards to view full details
- Apply directly from job details

### Application Management
- Login to view your applications
- Track application status
- Update profile information

## 🔒 Security Features

- **Password Hashing** - All passwords are securely hashed
- **SQL Injection Protection** - Prepared statements used throughout
- **Input Validation** - All user inputs are validated
- **Session Management** - Secure user sessions
- **File Upload Security** - Restricted file types for uploads

## 📊 Database Schema

### Users Table
```sql
- id (Primary Key)
- first_name, last_name
- email (Unique)
- password (Hashed)
- phone
- user_type (job_seeker/employer)
- created_at, updated_at
```

### Jobs Table
```sql
- id (Primary Key)
- title, description
- company_id (Foreign Key)
- category_id (Foreign Key)
- location, job_type
- salary_min, salary_max
- positions_available
- requirements, benefits
- status (active/inactive/closed)
- posted_by (Foreign Key)
- created_at, updated_at
```

### Applications Table
```sql
- id (Primary Key)
- job_id (Foreign Key)
- user_id (Foreign Key)
- resume_path
- cover_letter
- status (pending/reviewed/shortlisted/rejected/hired)
- applied_at, updated_at
```

## 🛠️ Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check if MySQL is running
   - Verify database credentials in `config.php`
   - Ensure database exists

2. **File Upload Issues**
   - Check `uploads/resumes/` directory permissions
   - Verify PHP upload settings

3. **API Errors**
   - Check browser console for JavaScript errors
   - Verify API endpoints are accessible
   - Check PHP error logs

### Debug Mode
- Enable PHP error reporting in `config.php`
- Check browser developer tools for JavaScript errors
- Monitor server error logs

## 📈 Future Enhancements

- Email notifications for applications
- Advanced job recommendations
- Company dashboard for employers
- Job alerts and saved searches
- Resume builder
- Interview scheduling
- Analytics and reporting

## 🤝 Support

For issues or questions:
1. Check the troubleshooting section
2. Review PHP and MySQL error logs
3. Verify all file permissions
4. Test with sample data first

## 📝 License

This project is open source and available under the MIT License.

---

**Note**: This is a development version. For production use, ensure proper security measures, SSL certificates, and regular backups.
