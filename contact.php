<?php
require_once 'config.php';
require_once 'includes/mail_functions.php';
require_once 'includes/functions.php';

$success = false;
$error = '';
$promotions = getActivePromotions($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        // Save message to database
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $message]);
            
            // Send email notification to Admin
            $adminSubject = "New Contact Message: " . $subject;
            $adminBody = "
                <h3>New Contact Message Received</h3>
                <p><strong>From:</strong> " . htmlspecialchars($name) . " (" . htmlspecialchars($email) . ")</p>
                <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
                <div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #667eea; margin: 15px 0;'>
                    " . nl2br(htmlspecialchars($message)) . "
                </div>
                <p>You can reply to this message from the admin panel.</p>
            ";
            
            // Send to Admin
            sendEmail(ADMIN_EMAIL, 'Admin', $adminSubject, $adminBody);
            
            $success = true;
        } catch (PDOException $e) {
            $error = 'Failed to send message. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Realm</title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/favicon.svg">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
</head>
<body>
    <?php include 'includes/bg_decorations.php'; ?>
    
    <header class="header">
        <div class="top-bar">
            <div class="container">
                <span><?php echo SITE_TAGLINE; ?></span>
            </div>
        </div>

        <div class="main-header">
            <div class="container">
                <div class="logo">
                    <a href="<?php echo BASE_URL; ?>">
                        <img src="<?php echo BASE_URL; ?>/assets/logo.png" alt="Realm" class="logo-img logo-img-nav">
                    </a>
                </div>

                <div class="search-bar">
                    <form method="GET" action="<?php echo BASE_URL; ?>/products.php">
                        <input type="text" name="search" placeholder="Search products..." id="searchInput">
                        <button type="submit" class="search-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </button>
                    </form>
                </div>

                <div class="header-right">
                    <a href="<?php echo BASE_URL; ?>/login.php" class="account-section">
                        <div class="account-icon-circle">
                            <svg class="account-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="account-info">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <span class="greeting">Hello, <?php echo escape($_SESSION['user_name']); ?></span>
                                <a href="<?php echo BASE_URL; ?>/logout.php" class="auth-link">Logout</a>
                            <?php else: ?>
                                <span class="greeting">Hello, Sign in</span>
                                <span class="auth-link">Login / Register</span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/wishlist.php" class="account-section wishlist-link">
                        <div class="account-icon-circle">
                            <svg class="account-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                        </div>
                        <div class="account-info">
                            <span class="greeting">Wishlist</span>
                            <span class="auth-link">View Saved</span>
                        </div>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/cart.php" class="cart-link" id="cartLink">
                        <div class="cart-icon-circle">
                            <div class="cart-icon-wrapper">
                                <svg class="cart-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="9" cy="21" r="1"></circle>
                                    <circle cx="20" cy="21" r="1"></circle>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                </svg>
                                <?php $cartCount = getCartCount($pdo); ?>
                                <?php if ($cartCount > 0): ?>
                                <span class="cart-badge"><?php echo $cartCount; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="cart-info">
                            <span class="cart-label">Your cart:</span>
                            <span class="cart-total">Ush <?php
                                $cartItems = getCartItems($pdo);
                                $total = 0;
                                foreach ($cartItems as $item) {
                                    $total += $item['price'] * $item['quantity'];
                                }
                                echo number_format($total, 0, '.', ',');
                            ?></span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <nav class="main-nav">
        <div class="container">
            <ul>
                <li><a href="<?php echo BASE_URL; ?>">HOME PAGE</a></li>
                <li><a href="<?php echo BASE_URL; ?>/products.php">ALL PRODUCTS</a></li>
                <li><a href="<?php echo BASE_URL; ?>/contact.php" class="active">CONTACT US</a></li>
                <li><a href="<?php echo BASE_URL; ?>/shipping.php">SHIPPING POLICY</a></li>
            </ul>
        </div>
    </nav>

    <!-- Promotional Slider -->
    <?php if (count($promotions) > 0): ?>
    <section class="promo-slider-section">
        <div class="container">
            <div class="promo-slider">
                <div class="slider-container" id="sliderContainer">
                    <?php foreach ($promotions as $promo): ?>
                    <div class="slide">
                        <img src="<?php echo BASE_URL; ?>/uploads/promotions/<?php echo $promo['image']; ?>" alt="<?php echo escape($promo['title']); ?>" onerror="this.src='https://via.placeholder.com/1200x400/667eea/ffffff?text=<?php echo urlencode($promo['title']); ?>'">
                        <div class="slide-content">
                            <h2><?php echo escape($promo['title']); ?></h2>
                            <p><?php echo escape($promo['description']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($promotions) > 1): ?>
                <button class="slider-nav prev" onclick="moveSlide(-1)">&#10094;</button>
                <button class="slider-nav next" onclick="moveSlide(1)">&#10095;</button>

                <div class="slider-dots">
                    <?php foreach ($promotions as $index => $promo): ?>
                    <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="currentSlide(<?php echo $index; ?>)"></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="contact-page">
        <div class="container">
            <h1 style="text-align: center;">Contact Us</h1>

            <div style="display: flex; justify-content: center; gap: 30px; align-items: flex-start; flex-wrap: wrap;">
                <!-- Contact Form Card - Centered -->
                <div class="contact-form-card">
                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        Thank you for contacting us! We will get back to you shortly.
                    </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" class="contact-form">
                        <div class="form-group">
                            <label>Your Name</label>
                            <input type="text" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Your Email</label>
                            <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="subject" required value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Message</label>
                            <textarea name="message" rows="6" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn-primary">Send Message</button>
                    </form>
                </div>

                <!-- WhatsApp Contact Button -->
                <div class="whatsapp-contact">
                    <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>" target="_blank" rel="noopener noreferrer" class="whatsapp-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                        <div>
                            <span class="whatsapp-label">Chat with us</span>
                            <span class="whatsapp-text">WhatsApp</span>
                        </div>
                    </a>

                    <div class="contact-info-sidebar">
                        <h3>Get In Touch</h3>
                        <p><strong>Phone:</strong></p>
                        <p><?= ADMIN_PHONE_1 ?></p>
                        <p><?= ADMIN_PHONE_2 ?></p>

                        <p><strong>Email:</strong></p>
                        <p><?= ADMIN_EMAIL ?></p>

                        <p><strong>Address:</strong></p>
                        <p>P.O.BOX 75720, PLOT NO 6</p>
                        <p>OPP. ENERGY CENTRE </p>
                        <p>MARKET STREET,NAKASEERO - KAMPALA,UGANDA</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <img src="<?php echo BASE_URL; ?>/assets/logo.png" alt="Realm" class="footer-logo-img logo-img-footer">
                    </div>
                    <div class="footer-contact">
                        <p><strong>Contact Us:</strong></p>
                        <p class="phone"><?= ADMIN_PHONE_1 ?></p>
                        <p class="phone"><?= ADMIN_PHONE_2 ?></p>
                        <p>Email: <?= ADMIN_EMAIL ?></p>
                        <p>Kampala, Uganda</p>
                    </div>
                </div>

                <div class="footer-col">
                    <h3>QUICK LINKS</h3>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/products.php">All Products</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/contact.php">Contact</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/shipping.php">Shipping</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h3>CATEGORIES</h3>
                    <ul>
                        <?php
                        $categories = getCategories($pdo);
                        foreach (array_slice($categories, 0, 6) as $cat):
                        ?>
                        <li><a href="<?php echo BASE_URL; ?>/products.php?category=<?php echo $cat['id']; ?>"><?php echo escape($cat['name']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="footer-col">
                    <h3>MY ACCOUNT</h3>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>/cart.php">Shopping Cart</a></li>
                        <li><a href="#">My Orders</a></li>
                        <li><a href="#">Wishlist</a></li>
                        <li><a href="#">Account Settings</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>Copyright &copy; 2025 Realm. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script src="<?php echo BASE_URL; ?>/js/script.js"></script>
    <script>
    // Promotional Slider
    document.addEventListener('DOMContentLoaded', function() {
        let slideIndex = 0;
        let autoSlideTimer;
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        const container = document.getElementById('sliderContainer');

        if (slides.length === 0 || !container) {
            return;
        }

        function showSlide(n) {
            if (n >= slides.length) slideIndex = 0;
            if (n < 0) slideIndex = slides.length - 1;

            container.style.transform = `translateX(-${slideIndex * 100}%)`;

            dots.forEach(dot => dot.classList.remove('active'));
            if (dots[slideIndex]) {
                dots[slideIndex].classList.add('active');
            }
        }

        window.moveSlide = function(n) {
            clearTimeout(autoSlideTimer);
            slideIndex += n;
            showSlide(slideIndex);
            startAutoSlide();
        }

        window.currentSlide = function(n) {
            clearTimeout(autoSlideTimer);
            slideIndex = n;
            showSlide(slideIndex);
            startAutoSlide();
        }

        function startAutoSlide() {
            clearTimeout(autoSlideTimer);
            autoSlideTimer = setTimeout(() => {
                slideIndex++;
                showSlide(slideIndex);
                startAutoSlide();
            }, 5000);
        }

        showSlide(slideIndex);
        if (slides.length > 1) {
            startAutoSlide();
        }
    });
    </script>
    <?php include 'includes/mobile_nav.php'; ?>
    <?php include 'includes/support_widget.php'; ?>
</body>
</html>
