-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 10, 2025 at 07:58 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `orders`
--

-- --------------------------------------------------------

--
-- Table structure for table `buyer`
--

CREATE TABLE `buyer` (
  `buyer_id` int(11) NOT NULL,
  `buyername` varchar(50) NOT NULL,
  `b_address` varchar(70) DEFAULT NULL,
  `b_city` varchar(30) DEFAULT NULL,
  `b_email` varchar(30) DEFAULT NULL,
  `b_contact` varchar(15) DEFAULT NULL,
  `b_productid` int(11) DEFAULT NULL,
  `b_status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buyer`
--

INSERT INTO `buyer` (`buyer_id`, `buyername`, `b_address`, `b_city`, `b_email`, `b_contact`, `b_productid`, `b_status`) VALUES
(1, 'Vimal', '123 Main Street', 'Colombo', 'Vimal@example.com', '0771234567', 1, 'Active'),
(2, 'Arun', '45 King Road', 'Kandy', 'arun@example.com', '0779876543', 2, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `order_table`
--

CREATE TABLE `order_table` (
  `order_id` varchar(20) NOT NULL,
  `product_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `order_address` varchar(255) DEFAULT NULL,
  `size` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `order_placed_date` date DEFAULT curdate(),
  `deadline_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Pending','Confirmed','Done') DEFAULT 'Pending',
  `payment_confirmation` tinyint(1) DEFAULT 0,
  `delivery_confirmation` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_table`
--

INSERT INTO `order_table` (`order_id`, `product_id`, `buyer_id`, `order_address`, `size`, `quantity`, `total_price`, `order_placed_date`, `deadline_date`, `description`, `status`, `payment_confirmation`, `delivery_confirmation`) VALUES
('ORD-001', 1, 1, '123 Main Street, Colombo', 10.00, 7, 1540.00, '2025-08-13', '2025-09-10', 'Customer requested faster delivery', 'Pending', 1, 0),
('ORD-002', 2, 2, '45 King Road, Kandy', 5.00, 12, 2166.00, '2025-08-15', '2025-09-05', '', 'Confirmed', 1, 1),
('ORD-003', 2, 1, '12/24 Mushroom Street, Tokyo', 5.00, 24, 21660.00, '2025-09-08', '2025-09-11', 'Popcorn kernels\r\n', 'Pending', 1, 0),
('ORD-004', 2, 2, '23/78 CauliStreet, Wattala', 5.00, 20, 18050.00, '2025-09-08', '2025-10-11', '', 'Pending', 0, 0),
('ORD-005', 3, 2, '54/25 Dinostreet, Colombo', 5.00, 5, 3750.00, '2025-09-08', '2025-10-03', 'Big Bulb Onions', 'Pending', 1, 0),
('ORD-006', 2, 2, '12/23 Gigidy', 5.00, 5, 4512.50, '2025-09-09', '2025-09-18', '', 'Pending', 1, 0),
('ORD-007', 2, 1, '124234234gergerth', 3.00, 5, 2707.50, '2025-09-09', '2025-09-12', 'Bruh', 'Pending', 1, 0),
('ORD-008', 3, 1, 'efsgdfea', 5.00, 3, 2250.00, '2025-09-09', '2025-09-12', '', 'Done', 1, 1),
('ORD-009', 3, 2, 'Wellago', 5.00, 5, 3750.00, '2025-09-09', '2025-09-19', '', 'Pending', 1, 0),
('ORD-010', 1, 1, 'Checking order', 5.00, 2, 2200.00, '2025-09-09', '2025-09-18', '', 'Confirmed', 1, 1),
('ORD-011', 2, 1, 'Checking order #2', 5.00, 2, 1805.00, '2025-09-09', '2025-09-10', '', 'Confirmed', 1, 1),
('ORD-012', 1, 1, 'Testing ordering conn', 2.00, 5, 2200.00, '2025-09-09', '2025-09-11', '', 'Pending', 1, 0),
('ORD-013', 2, 1, 'Testing conn #2', 4.00, 3, 2166.00, '2025-09-09', '2025-09-17', '', 'Confirmed', 1, 1),
('ORD-014', 3, 2, 'Skbiidi rizz', 5.00, 3, 2250.00, '2025-09-09', '2025-09-19', '', 'Done', 1, 1),
('ORD-015', 2, 2, 'Shakalaka', 5.00, 5, 4512.50, '2025-09-09', '2025-09-16', '', 'Confirmed', 1, 1),
('ORD-016', 2, 2, 'Guava juicee', 5.00, 2, 1805.00, '2025-09-10', '2025-09-14', '', 'Pending', 0, 0),
('ORD-017', 2, 2, 'Skididly', 4.00, 1, 722.00, '2025-09-10', '2025-09-01', '', 'Pending', 0, 0),
('ORD-018', 1, 1, 'Waoaoaoo', 5.00, 20, 22000.00, '2025-09-10', '2025-09-01', '', 'Done', 1, 1),
('ORD-019', 3, 1, 'Testing done', 10.00, 5, 7500.00, '2025-09-10', '2025-08-20', '', 'Done', 1, 1),
('ORD-020', 2, 1, 'Testing mid', 5.00, 2, 1805.00, '2025-09-10', '2025-09-13', '', 'Pending', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `ProductID` int(11) NOT NULL,
  `ProductName` varchar(100) NOT NULL,
  `ReorderPoint` int(11) DEFAULT 0,
  `PricePerKg` decimal(10,2) NOT NULL,
  `Status` enum('Active','Inactive') DEFAULT 'Active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`ProductID`, `ProductName`, `ReorderPoint`, `PricePerKg`, `Status`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 'Urid Dhal', 40, 220.00, 'Active', '2025-09-08 14:06:35', '2025-09-08 14:06:35'),
(2, 'Popcorn', 30, 180.50, 'Active', '2025-09-08 14:06:35', '2025-09-08 14:06:35'),
(3, 'Onion', 30, 150.00, 'Active', '2025-09-08 14:06:35', '2025-09-08 14:06:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buyer`
--
ALTER TABLE `buyer`
  ADD PRIMARY KEY (`buyer_id`),
  ADD KEY `fk_buyer_product` (`b_productid`);

--
-- Indexes for table `order_table`
--
ALTER TABLE `order_table`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_order_product` (`product_id`),
  ADD KEY `fk_order_buyer` (`buyer_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`ProductID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buyer`
--
ALTER TABLE `buyer`
  MODIFY `buyer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `ProductID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buyer`
--
ALTER TABLE `buyer`
  ADD CONSTRAINT `fk_buyer_product` FOREIGN KEY (`b_productid`) REFERENCES `product` (`ProductID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `order_table`
--
ALTER TABLE `order_table`
  ADD CONSTRAINT `fk_order_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `buyer` (`buyer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`ProductID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
