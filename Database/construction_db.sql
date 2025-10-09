-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 09, 2025 at 08:29 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `construction_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `bulk_orders`
--

CREATE TABLE `bulk_orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bulk_orders`
--

INSERT INTO `bulk_orders` (`order_id`, `customer_id`, `material_id`, `quantity`, `status`, `created_at`) VALUES
(1, 3, 4, 22.00, 'closed', '2025-10-08 08:35:28'),
(3, 3, 3, 333.00, 'open', '2025-10-09 05:38:59');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `chat_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`chat_id`, `sender_id`, `receiver_id`, `message`, `is_read`, `sent_at`) VALUES
(1, 3, 2, 'Hy', 0, '2025-10-08 07:51:19'),
(2, 2, 3, 'Yes', 0, '2025-10-08 08:04:02');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `coupon_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_percent` int(11) DEFAULT NULL CHECK (`discount_percent` between 1 and 100),
  `expire` date DEFAULT NULL,
  `sender_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`coupon_id`, `code`, `title`, `description`, `discount_percent`, `expire`, `sender_id`) VALUES
(1, 'DFA', '', 'fda', 44, '2025-10-10', 2),
(2, 'L9U7', '', 'cxz', 66, '2025-10-11', 2),
(3, 'ZY', 'XYZ', 'XYZ', 22, '2025-11-08', 2);

-- --------------------------------------------------------

--
-- Table structure for table `credit_payments`
--

CREATE TABLE `credit_payments` (
  `credit_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `total_credit` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `credit_payments`
--

INSERT INTO `credit_payments` (`credit_id`, `id`, `order_id`, `total_credit`) VALUES
(1, 2, 4, 300.00),
(2, 2, 3, 300.00);

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `material_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('cement','bricks','steel','wood','tiles','sand','gravel','concrete','glass','plumbing','electrical','paint','roofing','insulation','other') DEFAULT 'other',
  `description` text DEFAULT NULL,
  `wholesale_price` decimal(10,2) NOT NULL,
  `retail_price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `low_stock_threshold` int(11) DEFAULT 10,
  `unit_of_measure` enum('kg','ton','piece','square_meter','cubic_meter','liter','meter','bundle','other') DEFAULT 'other',
  `min_order_quantity` int(11) DEFAULT 1,
  `bulk_discount_percentage` decimal(5,2) DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `supplier_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`material_id`, `supplier_id`, `name`, `category`, `description`, `wholesale_price`, `retail_price`, `stock_quantity`, `low_stock_threshold`, `unit_of_measure`, `min_order_quantity`, `bulk_discount_percentage`, `image`, `status`, `created_at`, `updated_at`, `supplier_notes`) VALUES
(1, 2, 'Material One', 'cement', 'XYZ', 230.00, 240.00, 267, 10, '', 1, 20.00, 'uploads/1759906783_Gemini_Generated_Image_m4tzecm4tzecm4tz.png', 'active', '2025-10-08 06:59:43', '2025-10-08 07:48:48', 'XYZ'),
(2, 2, 'Portland Cement', 'cement', 'High-quality Portland cement ideal for construction.', 500.00, 650.00, 120, 20, '', 10, 5.00, 'uploads/1759906783_Gemini_Generated_Image_m4tzecm4tzecm4tz.png', 'inactive', '2025-10-08 07:05:28', '2025-10-08 07:15:40', 'Keep in dry area'),
(3, 2, 'Red Clay Bricks', 'bricks', 'Durable red clay bricks for walls and structures.', 8.00, 12.00, 2399, 500, 'piece', 100, 10.00, 'uploads/1759906783_Gemini_Generated_Image_m4tzecm4tzecm4tz.png', 'active', '2025-10-08 07:05:28', '2025-10-09 04:56:32', 'Best for residential use'),
(4, 2, 'Steel Rod 12mm', 'steel', '12mm high-strength steel rods for reinforcement.', 1500.00, 1800.00, 300, 50, 'piece', 10, 8.00, 'uploads/1759906783_Gemini_Generated_Image_m4tzecm4tzecm4tz.png', 'active', '2025-10-08 07:05:28', '2025-10-08 07:05:28', 'Deliver in bundles'),
(5, 2, 'Pine Wood Plank', 'wood', 'Treated pine wood planks suitable for furniture.', 900.00, 1100.00, 20, 30, '', 5, 6.00, 'uploads/1759906783_Gemini_Generated_Image_m4tzecm4tzecm4tz.png', 'active', '2025-10-08 07:05:28', '2025-10-08 07:05:53', 'Avoid moisture exposure'),
(6, 2, 'Ceramic Tiles 12x12', 'tiles', 'Glossy ceramic tiles for flooring and walls.', 50.00, 75.00, 1000, 100, 'square_meter', 20, 7.00, 'uploads/1759906783_Gemini_Generated_Image_m4tzecm4tzecm4tz.png', 'active', '2025-10-08 07:05:28', '2025-10-08 07:05:28', 'Handle with care'),
(7, 2, 'River Sand', 'sand', 'Clean river sand for general construction use.', 2000.00, 2500.00, 40, 10, 'ton', 5, 4.00, 'uploads/1759906783_Gemini_Generated_Image_m4tzecm4tzecm4tz.png', 'active', '2025-10-08 07:05:28', '2025-10-08 07:05:28', 'Use for concrete mix'),
(8, 2, 'Crushed Gravel', 'gravel', 'Fine crushed gravel ideal for concrete foundations.', 1800.00, 2300.00, 25, 5, 'ton', 3, 3.50, 'uploads/1759906783_Gemini_Generated_Image_m4tzecm4tzecm4tz.png', 'active', '2025-10-08 07:05:28', '2025-10-08 07:05:28', 'Ensure uniform mix'),
(9, 2, 'Glass Sheet 6mm', 'glass', 'Transparent 6mm glass sheets for windows.', 900.00, 1200.00, 75, 10, 'square_meter', 5, 5.00, 'uploads/1759906783_Gemini_Generated_Image_m4tzecm4tzecm4tz.png', 'active', '2025-10-08 07:05:28', '2025-10-08 07:05:28', 'Fragile handle carefully'),
(10, 2, 'PVC Pipe 2 Inch', 'plumbing', 'Durable 2-inch PVC pipe for plumbing systems.', 400.00, 550.00, 200, 25, 'meter', 10, 6.50, 'uploads/1759906783_Gemini_Generated_Image_m4tzecm4tzecm4tz.png', 'active', '2025-10-08 07:05:28', '2025-10-08 07:05:28', 'Keep away from sunlight'),
(11, 2, 'Wall Paint White', 'paint', 'Premium white wall paint with smooth finish.', 1200.00, 1600.00, 60, 10, 'liter', 5, 8.00, 'uploads/1759906783_Gemini_Generated_Image_m4tzecm4tzecm4tz.png', 'inactive', '2025-10-08 07:05:28', '2025-10-09 06:04:05', 'Shake well before use');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `title`, `message`, `created_at`) VALUES
(1, 'New Bulk Order Placed', 'Customer ID 3 placed a bulk order for Material ID 4.', '2025-10-08 08:38:36'),
(2, 'New Bulk Order', 'Customer ID 3 placed a bulk order for material ID 3 (qty: 333).', '2025-10-09 05:38:59');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('paid','pending','failed') DEFAULT 'pending',
  `order_status` enum('processing','shipped','delivered','cancelled') DEFAULT 'processing',
  `location` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `id`, `total_amount`, `payment_status`, `order_status`, `location`, `created_at`) VALUES
(2, 3, 2760.00, 'paid', 'shipped', 'Lahore', '2025-10-08 07:23:17'),
(3, 3, 6336.00, 'pending', 'processing', 'model town lahore', '2025-10-08 07:48:48'),
(4, 3, 132.00, 'pending', 'processing', 'Lahore', '2025-10-08 08:22:26'),
(5, 3, 240.00, 'pending', 'processing', 'Dummy Job', '2025-10-09 04:54:11'),
(6, 3, 240.00, 'pending', 'processing', 'Dummy Job', '2025-10-09 04:54:41'),
(7, 3, 240.00, 'pending', 'delivered', 'Dummy Job', '2025-10-09 04:56:32');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `material_id`, `quantity`, `price`, `subtotal`) VALUES
(2, 2, 3, 230, 12.00, 2760.00),
(3, 3, 1, 33, 240.00, 7920.00),
(4, 4, 3, 11, 12.00, 132.00),
(5, 5, 3, 20, 12.00, 240.00),
(6, 6, 3, 20, 12.00, 240.00),
(7, 7, 3, 20, 12.00, 240.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `payment_method` enum('credit_card','debit_card','upi','cod','credit') DEFAULT 'cod',
  `amount` decimal(10,2) NOT NULL,
  `receipt` varchar(255) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_id`, `customer_id`, `payment_method`, `amount`, `receipt`, `payment_date`) VALUES
(1, 2, 3, 'upi', 2760.00, '../uploads/receipts/1759908621_invoice_order_2.pdf', '2025-10-08 07:30:21');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `supplier_id`, `id`, `rating`, `comment`, `created_at`) VALUES
(2, 2, 3, 4, 'Ex', '2025-10-08 08:10:33');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_bids`
--

CREATE TABLE `supplier_bids` (
  `bid_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `bulk_order_id` int(11) NOT NULL,
  `bid_price` decimal(10,2) NOT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier_bids`
--

INSERT INTO `supplier_bids` (`bid_id`, `supplier_id`, `bulk_order_id`, `bid_price`, `status`, `created_at`) VALUES
(1, 2, 1, 22.00, 'accepted', '2025-10-08 08:49:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('wholesaler','retailer','customer') NOT NULL,
  `status` enum('approved','pending','rejected') DEFAULT 'approved',
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `phone`, `address`, `created_at`) VALUES
(2, 'WholeSaler', 'sale@gmail.com', '$2y$10$qNnV0R/J01HYKMTOYaF65Oq0Oyz7Ms.z6JCbH3L7fDyn06xlM5RaS', 'wholesaler', 'approved', '03176526827', 'Street jeff xxxx', '2025-10-08 06:33:59'),
(3, 'Customer', 'kainatsofi.8bp@gmail.com', '$2y$10$3ggf.LCWvmxUGJDOEkg0PeS8j3GJSIu.Oesr5NLZvqRw225aVf9A2', 'customer', 'approved', '03120558090', 'P/o Basti Chhina Tehsil jampur District RajanpurP/o Basti Chhina Tehsil jampur District Rajanpur', '2025-10-08 07:10:48'),
(4, 'Retailer', 'retailer@gmail.com', '$2y$10$r3Mv7.wkZkk7ePttUD3Cbu5kMX8EbgatjOGSLbuTFwfZXAe.T6L7G', 'retailer', 'approved', '03266029059', 'ds', '2025-10-09 06:16:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bulk_orders`
--
ALTER TABLE `bulk_orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`chat_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`coupon_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `credit_payments`
--
ALTER TABLE `credit_payments`
  ADD PRIMARY KEY (`credit_id`),
  ADD KEY `id` (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`material_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `supplier_bids`
--
ALTER TABLE `supplier_bids`
  ADD PRIMARY KEY (`bid_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `bulk_order_id` (`bulk_order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bulk_orders`
--
ALTER TABLE `bulk_orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `chat_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `coupon_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `credit_payments`
--
ALTER TABLE `credit_payments`
  MODIFY `credit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `material_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `supplier_bids`
--
ALTER TABLE `supplier_bids`
  MODIFY `bid_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bulk_orders`
--
ALTER TABLE `bulk_orders`
  ADD CONSTRAINT `bulk_orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bulk_orders_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `materials` (`material_id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coupons`
--
ALTER TABLE `coupons`
  ADD CONSTRAINT `coupons_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `credit_payments`
--
ALTER TABLE `credit_payments`
  ADD CONSTRAINT `credit_payments_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `credit_payments_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `materials`
--
ALTER TABLE `materials`
  ADD CONSTRAINT `materials_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `materials` (`material_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_bids`
--
ALTER TABLE `supplier_bids`
  ADD CONSTRAINT `supplier_bids_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplier_bids_ibfk_2` FOREIGN KEY (`bulk_order_id`) REFERENCES `bulk_orders` (`order_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
