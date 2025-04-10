-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 09, 2025 at 05:12 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shopp`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `username`, `password`, `name`, `address`) VALUES
(1, 'customer1', '$2y$10$WAKeX1tMzM1Yg5z5z5z5z5uWAKeX1tMzM1Yg5z5z5z5z5uWAKeX1tM', 'Amit', '123 Village Lane'),
(2, 'tunki', '$2y$10$1Mln.wQ1/t6A1s8yo6zcxuixXGG9Y52pv8Vg5eq89uEQXDF8R6fmy', NULL, NULL),
(3, 'Mounika', '$2y$10$3y1/tL9lVuO6KXFbEfG0CegovgxEyfM2AnMqkcMhzzRGHHua7MRpm', NULL, NULL),
(4, 'hari', '$2y$10$yKxS51tS9A0lx0UZ2uLgJeOE51QMWIqRdZ0N7hrPCqJOwqg687sVK', 'hari', 'hyd'),
(5, 'prem', '$2y$10$SWiM.ZMrHe79wSufqgm8WOweWtRT7nURgJyEFyNsllpXViObsyShq', 'prem', 'at temple');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_details`
--

CREATE TABLE `delivery_details` (
  `order_id` int(11) NOT NULL,
  `delivery_type` enum('delivery','pickup') DEFAULT 'pickup',
  `delivery_charge` int(11) DEFAULT 0,
  `delivery_address` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_details`
--

INSERT INTO `delivery_details` (`order_id`, `delivery_type`, `delivery_charge`, `delivery_address`) VALUES
(1, 'delivery', 50, 'rjp'),
(2, 'pickup', 0, NULL),
(3, 'pickup', 0, NULL),
(4, 'delivery', 50, 'hyd'),
(5, 'pickup', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `shop_id` int(11) DEFAULT NULL,
  `order_date` datetime DEFAULT NULL,
  `status` enum('Pending','Shipped','Delivered','Cancelled') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `shop_id`, `order_date`, `status`) VALUES
(1, 2, 2, '2025-04-01 10:14:47', 'Shipped'),
(2, 2, 1, '2025-04-01 11:13:12', 'Shipped'),
(3, 3, 2, '2025-04-01 11:39:40', 'Delivered'),
(4, 4, 2, '2025-04-08 14:55:51', 'Cancelled'),
(5, 2, 1, '2025-04-09 15:25:32', 'Delivered');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_id`, `product_id`, `quantity`) VALUES
(1, 3, 10),
(1, 4, 20),
(2, 1, 4),
(2, 2, 3),
(2, 5, 4),
(3, 3, 3),
(3, 4, 20),
(3, 6, 30),
(4, 3, 90),
(4, 4, 55),
(4, 6, 90),
(5, 1, 9),
(5, 2, 10),
(5, 5, 20);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `shop_id` int(11) DEFAULT NULL,
  `name` varchar(50) NOT NULL,
  `price` int(11) NOT NULL,
  `stock` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `shop_id`, `name`, `price`, `stock`) VALUES
(1, 1, 'Rice', 50, 91),
(2, 1, 'Flour', 30, 190),
(3, 2, 'Sugar', 40, 60),
(4, 2, 'Salt', 20, 245),
(5, 1, 'Flake seeds', 60, 50),
(6, 2, 'Mirchi', 70, 110),
(8, 1, 'tea power', 30, 100);

-- --------------------------------------------------------

--
-- Table structure for table `shops`
--

CREATE TABLE `shops` (
  `shop_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `address` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shops`
--

INSERT INTO `shops` (`shop_id`, `name`, `address`) VALUES
(1, 'Village Store 1', '123 Main St'),
(2, 'Village Stor 2', '456 Market Rd');

-- --------------------------------------------------------

--
-- Table structure for table `store_admins`
--

CREATE TABLE `store_admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `shop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_admins`
--

INSERT INTO `store_admins` (`admin_id`, `username`, `password`, `shop_id`) VALUES
(1, 'admin1', '$2y$10$WAKeX1tMzM1Yg5z5z5z5z5uWAKeX1tMzM1Yg5z5z5z5z5uWAKeX1tM', 1),
(2, 'admin2', '$2y$10$WAKeX1tMzM1Yg5z5z5z5z5uWAKeX1tMzM1Yg5z5z5z5z5uWAKeX1tM', 2),
(3, 'badam', '$2y$10$m1TdzXir9M8OJ26O9JXDXO3BcX3iTYo3Y03.ceTexf072LLXkAbQK', 1),
(4, 'mehul', '$2y$10$/.ScFUFOS7A52mSdFHVOLOWDlYELlN1WBAc74ev1m/gPBP9U7ZJkq', 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `delivery_details`
--
ALTER TABLE `delivery_details`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`shop_id`);

--
-- Indexes for table `store_admins`
--
ALTER TABLE `store_admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `shop_id` (`shop_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `shops`
--
ALTER TABLE `shops`
  MODIFY `shop_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `store_admins`
--
ALTER TABLE `store_admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `delivery_details`
--
ALTER TABLE `delivery_details`
  ADD CONSTRAINT `delivery_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`shop_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`shop_id`);

--
-- Constraints for table `store_admins`
--
ALTER TABLE `store_admins`
  ADD CONSTRAINT `store_admins_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`shop_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
