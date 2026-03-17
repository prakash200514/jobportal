<?php
require_once 'config.php';

// Session and user
session_start();
$loggedInUser = null;
if (isset($_SESSION['user_id'])) {
  try {
    $pdoUser = getDBConnection();
    $stmtUser = $pdoUser->prepare("SELECT * FROM users WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $loggedInUser = $stmtUser->fetch();
  } catch (Exception $e) { /* ignore */ }
}

// Get data from database
$pdo = getDBConnection();

// Get job categories
$stmt = $pdo->query("SELECT * FROM job_categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

// Get latest jobs
$stmt = $pdo->query("SELECT j.*, c.name as company_name, c.logo as company_logo, c.location as company_location,
                            cat.name as category_name, cat.icon as category_icon
                     FROM jobs j
                     LEFT JOIN companies c ON j.company_id = c.id
                     LEFT JOIN job_categories cat ON j.category_id = cat.id
                     WHERE j.status = 'active'
                     ORDER BY j.created_at DESC
                     LIMIT 6");
$latestJobs = $stmt->fetchAll();

// Get testimonials
$stmt = $pdo->query("SELECT * FROM testimonials WHERE status = 'active' ORDER BY created_at DESC");
$testimonials = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link
      href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"
    />
    <link rel="stylesheet" href="styles.css" />
    <title>Web Design Mastery | JobHunt</title>
  </head>
  <body>
    <nav>
      <div class="nav__header">
        <div class="nav__logo">
          <a href="#" class="logo">Job<span>Hunt</span></a>
        </div>
        <div class="nav__menu__btn" id="menu-btn">
          <i class="ri-menu-line"></i>
        </div>
      </div>
      <ul class="nav__links" id="nav-links" style="z-index:1000">
        <?php if ($loggedInUser && $loggedInUser['user_type'] === 'employer'): ?>
          <li><a href="#home">Home</a></li>
          <li><a href="jobs-list.php">Browse Jobs</a></li>
          <li><a href="add-job.php" class="btn">Post Job</a></li>
          <li><a href="my-jobs.php" class="btn">My Jobs</a></li>
          <li><a href="logout.php" class="btn btn--outline">Logout</a></li>
        <?php else: ?>
          <li><a href="#home">Home</a></li>
          <li><a href="#about">About</a></li>
          <li><a href="jobs-list.php">Jobs</a></li>
          <li><a href="#service">Services</a></li>
          <li><a href="#client">Client</a></li>
          <li><button class="btn" onclick="showLoginModal()">Login</button></li>
          <li><button class="btn" onclick="showRegisterModal()">Register</button></li>
        <?php endif; ?>
      </ul>
    </nav>
    <header class="section__container header__container" id="home">
      <img src="assets/google.png" alt="header" />
      <img src="assets/twitter.png" alt="header" />
      <img src="assets/amazon.png" alt="header" />
      <img src="assets/figma.png" alt="header" />
      <img src="assets/linkedin.png" alt="header" />
      <img src="assets/microsoft.png" alt="header" />
      <h2>
        <img src="assets/bag.png" alt="bag" />
        No.1 Job Hunt Website
      </h2>
      <h1>Search, Apply &<br />Get Your <span>Dream Job</span></h1>
      <p>
        Your future starts here. Discover countless opportunities, take action
        by applying to jobs that match your skills and aspirations, and
        transform your career.
      </p>
      <div class="header__btns">
        <button class="btn" onclick="scrollToJobs()">Browse Jobs</button>
        <a href="#">
          <span><i class="ri-play-fill"></i></span>
          How It Works?
        </a>
      </div>
    </header>

    <section class="steps" id="about">
      <div class="section__container steps__container">
        <h2 class="section__header">
          Get Hired in 4 <span>Quick Easy Steps</span>
        </h2>
        <p class="section__description">
          Follow Our Simple, Step-by-Step Guide to Quickly Land Your Dream Job
          and Start Your New Career Journey.
        </p>
        <div class="steps__grid">
          <div class="steps__card">
            <span><i class="ri-user-fill"></i></span>
            <h4>Create an Account</h4>
            <p>
              Sign up with just a few clicks to unlock exclusive access to a
              world of job opportunities and landing your dream job. It's quick,
              easy, and completely free.
            </p>
          </div>
          <div class="steps__card">
            <span><i class="ri-search-fill"></i></span>
            <h4>Search Job</h4>
            <p>
              Dive into our job database tailored to match your skills and
              preferences. With our advanced search filters, finding the perfect
              job has never been easier.
            </p>
          </div>
          <div class="steps__card">
            <span><i class="ri-file-paper-fill"></i></span>
            <h4>Upload CV/Resume</h4>
            <p>
              Showcase your experience by uploading your CV or resume. Let
              employers know why you're the perfect candidate for their job
              openings.
            </p>
          </div>
          <div class="steps__card">
            <span><i class="ri-briefcase-fill"></i></span>
            <h4>Get Job</h4>
            <p>
              Take the final step towards your new career. Get ready to embark
              on your professional journey and secure the job you've been
              dreaming of.
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="section__container explore__container">
      <h2 class="section__header">
        <span>Countless Career Options</span> Are Waiting For You To Explore
      </h2>
      <p class="section__description">
        Discover a World of Exciting Opportunities and Endless Possibilities,
        and Find the Perfect Career Path to Shape Your Future.
      </p>
      <div class="explore__grid">
        <?php foreach ($categories as $category): ?>
        <div class="explore__card" onclick="filterJobsByCategory('<?php echo htmlspecialchars($category['name']); ?>')">
          <span><i class="<?php echo htmlspecialchars($category['icon']); ?>"></i></span>
          <h4><?php echo htmlspecialchars($category['name']); ?></h4>
          <p><?php echo $category['job_count']; ?>+ jobs openings</p>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="explore__btn">
        <button class="btn" onclick="scrollToJobs()">View All Categories</button>
      </div>
    </section>

    <section class="section__container job__container" id="job">
      <h2 class="section__header"><span>Latest & Top</span> Job Openings</h2>
      <p class="section__description">
        Discover Exciting New Opportunities and High-Demand Positions Available
        Now in Top Industries and Companies
      </p>

      

      <div class="job__grid" id="job-grid">
        <?php foreach ($latestJobs as $job): ?>
        <div class="job__card" data-category="<?php echo htmlspecialchars($job['category_name']); ?>">
          <div class="job__card__header">
            <img src="<?php echo htmlspecialchars($job['company_logo']); ?>" alt="job" />
            <div>
              <h5><?php echo htmlspecialchars($job['company_name']); ?></h5>
              <h6><?php echo htmlspecialchars($job['location']); ?></h6>
            </div>
          </div>
          <h4><?php echo htmlspecialchars($job['title']); ?></h4>
          <p><?php echo htmlspecialchars(substr($job['description'], 0, 150)) . '...'; ?></p>
          <div class="job__card__footer">
            <span><?php echo $job['positions_available']; ?> Positions</span>
            <span><?php echo htmlspecialchars($job['job_type']); ?></span>
            <span>
              <?php if ($job['salary_min'] && $job['salary_max']): ?>
                $<?php echo number_format($job['salary_min']); ?>-<?php echo number_format($job['salary_max']); ?>/Year
              <?php else: ?>
                Salary Not Specified
              <?php endif; ?>
            </span>
          </div>
          <div class="job__card__actions">
            <button class="btn btn--small" onclick="viewJobDetails(<?php echo $job['id']; ?>)">View Details</button>
            <button class="btn btn--outline" onclick="applyToJob(<?php echo $job['id']; ?>)">Apply Now</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      
      <div class="job__pagination" id="job-pagination" style="display: none;">
        <button class="btn" onclick="loadMoreJobs()">Load More Jobs</button>
      </div>
    </section>

    <section class="section__container offer__container" id="service">
      <h2 class="section__header">What We <span>Offer</span></h2>
      <p class="section__description">
        Explore the Benefits and Services We Provide to Enhance Your Job Search
        and Career Success
      </p>
      <div class="offer__grid">
        <div class="offer__card">
          <img src="assets/offer-1.jpg" alt="offer" />
          <div class="offer__details">
            <span>01</span>
            <div>
              <h4>Job Recommendation</h4>
              <p>
                Personalized job matches tailored to your skills and preferences
              </p>
            </div>
          </div>
        </div>
        <div class="offer__card">
          <img src="assets/offer-2.jpg" alt="offer" />
          <div class="offer__details">
            <span>02</span>
            <div>
              <h4>Create & Build Portfolio</h4>
              <p>Showcase your expertise with professional portfolio design</p>
            </div>
          </div>
        </div>
        <div class="offer__card">
          <img src="assets/offer-3.jpg" alt="offer" />
          <div class="offer__details">
            <span>03</span>
            <div>
              <h4>Career Consultation</h4>
              <p>Receive expert advice to navigate your career path</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section__container client__container" id="client">
      <h2 class="section__header">What Our <span>Client Say</span></h2>
      <p class="section__description">
        Read Testimonials and Success Stories from Our Satisfied Job Seekers and
        Employers to See How We Make a Difference
      </p>
      <!-- Slider main container -->
      <div class="swiper">
        <!-- Additional required wrapper -->
        <div class="swiper-wrapper">
          <!-- Slides -->
          <?php foreach ($testimonials as $testimonial): ?>
          <div class="swiper-slide">
            <div class="client__card">
              <img src="<?php echo htmlspecialchars($testimonial['image']); ?>" alt="client" />
              <p><?php echo htmlspecialchars($testimonial['content']); ?></p>
              <div class="client__ratings">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <?php if ($i <= $testimonial['rating']): ?>
                    <span><i class="ri-star-fill"></i></span>
                  <?php else: ?>
                    <span><i class="ri-star-line"></i></span>
                  <?php endif; ?>
                <?php endfor; ?>
              </div>
              <h4><?php echo htmlspecialchars($testimonial['name']); ?></h4>
              <h5><?php echo htmlspecialchars($testimonial['position']); ?></h5>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <footer class="footer">
      <div class="section__container footer__container">
        <div class="footer__col">
          <div class="footer__logo">
            <a href="#" class="logo">Job<span>Hunt</span></a>
          </div>
          <p>
            Our platform is designed to help you find the perfect job and
            achieve your professional dreams.
          </p>
        </div>
        <div class="footer__col">
          <h4>Quick Links</h4>
          <ul class="footer__links">
            <li><a href="#">Home</a></li>
            <li><a href="#">About Us</a></li>
            <li><a href="#">Jobs</a></li>
            <li><a href="#">Testimonials</a></li>
            <li><a href="#">Contact Us</a></li>
          </ul>
        </div>
        <div class="footer__col">
          <h4>Follow Us</h4>
          <ul class="footer__links">
            <li><a href="#">Facebook</a></li>
            <li><a href="#">Instagram</a></li>
            <li><a href="#">LinkedIn</a></li>
            <li><a href="#">Twitter</a></li>
            <li><a href="#">Youtube</a></li>
          </ul>
        </div>
        <div class="footer__col">
          <h4>Contact Us</h4>
          <ul class="footer__links">
            <li>
              <a href="#">
                <span><i class="ri-phone-fill"></i></span> +91 234 56788
              </a>
            </li>
            <li>
              <a href="#">
                <span><i class="ri-map-pin-2-fill"></i></span> 123 Main Street,
                Anytown, USA
              </a>
            </li>
          </ul>
        </div>
      </div>
      <div class="footer__bar">
        Copyright © 2024 Web Design Mastery. All rights reserved.
      </div>
    </footer>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
      <div class="modal-content">
        <span class="close" onclick="closeModal('loginModal')">&times;</span>
        <h2>Login</h2>
        <form id="loginForm">
          <div class="form-group">
            <label for="loginEmail">Email:</label>
            <input type="email" id="loginEmail" name="email" required>
          </div>
          <div class="form-group">
            <label for="loginPassword">Password:</label>
            <input type="password" id="loginPassword" name="password" required>
          </div>
          <button type="submit" class="btn">Login</button>
        </form>
      </div>
    </div>

    <!-- Register Modal -->
    <div id="registerModal" class="modal">
      <div class="modal-content">
        <span class="close" onclick="closeModal('registerModal')">&times;</span>
        <h2>Register</h2>
        <form id="registerForm">
          <div class="form-group">
            <label for="regFirstName">First Name:</label>
            <input type="text" id="regFirstName" name="first_name" required>
          </div>
          <div class="form-group">
            <label for="regLastName">Last Name:</label>
            <input type="text" id="regLastName" name="last_name" required>
          </div>
          <div class="form-group">
            <label for="regEmail">Email:</label>
            <input type="email" id="regEmail" name="email" required>
          </div>
          <div class="form-group">
            <label for="regPassword">Password:</label>
            <input type="password" id="regPassword" name="password" required>
          </div>
          <div class="form-group">
            <label for="regPhone">Phone:</label>
            <input type="tel" id="regPhone" name="phone">
          </div>
          <div class="form-group">
            <label for="regUserType">User Type:</label>
            <select id="regUserType" name="user_type">
              <option value="job_seeker">Job Seeker</option>
              <option value="employer">Employer</option>
            </select>
          </div>
          <button type="submit" class="btn">Register</button>
        </form>
      </div>
    </div>

    <!-- Job Details Modal -->
    <div id="jobDetailsModal" class="modal">
      <div class="modal-content">
        <span class="close" onclick="closeModal('jobDetailsModal')">&times;</span>
        <div id="jobDetailsContent">
          <!-- Job details will be loaded here -->
        </div>
      </div>
    </div>

    <!-- Application Modal -->
    <div id="applicationModal" class="modal">
      <div class="modal-content">
        <span class="close" onclick="closeModal('applicationModal')">&times;</span>
        <h2>Apply for Job</h2>
        <form id="applicationForm" enctype="multipart/form-data">
          <input type="hidden" id="applicationJobId" name="job_id">
          <div class="form-group">
            <label for="resumeFile">Upload Resume:</label>
            <input type="file" id="resumeFile" name="resume" accept=".pdf,.doc,.docx">
          </div>
          <div class="form-group">
            <label for="coverLetter">Cover Letter:</label>
            <textarea id="coverLetter" name="cover_letter" rows="5"></textarea>
          </div>
          <button type="submit" class="btn">Submit Application</button>
        </form>
      </div>
    </div>

    <script src="https://unpkg.com/scrollreveal"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="main-db.js"></script>
  </body>
</html>
