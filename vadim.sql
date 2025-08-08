-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Хост: mariadb:3306
-- Время создания: Авг 07 2025 г., 10:27
-- Версия сервера: 11.7.2-MariaDB-ubu2404
-- Версия PHP: 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `vadim`
--

-- --------------------------------------------------------

--
-- Структура таблицы `manufacturers`
--

CREATE TABLE `manufacturers` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `manufacturers`
--

INSERT INTO `manufacturers` (`id`, `name`, `slug`) VALUES
(1, 'Altaya', 'altaya'),
(2, 'A-model', 'a-model'),
(3, 'Hot Wheels', 'hot-wheels'),
(4, 'PERFEX', 'perfex');

-- --------------------------------------------------------

--
-- Структура таблицы `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `manufacturer_id` int(10) UNSIGNED NOT NULL,
  `scale_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `products`
--

INSERT INTO `products` (`id`, `name`, `slug`, `manufacturer_id`, `scale_id`) VALUES
(1, 'Baja Truck No.33, orange', 's02-fyy72-k710-baja-truck-no-33-orange', 3, 59),
(2, 'WILLEME T40a Tractor Truck 8x8 Tidelium Trasporto Carri Tank Transports (porte-char Creusot-loire Industrie) 4-assi (1983), Military Sand', 'pe920-willeme-t40a-tractor-truck-8x8-tidelium-trasporto-carri-tank-transports-porte-char-creusot-loire-industrie-4-assi-1983-military-sand', 4, 39),
(3, 'VOLKSWAGEN Concept A, Concept Cars', 'con030-volkswagen-concept-a-concept-cars', 1, 39);

-- --------------------------------------------------------

--
-- Структура таблицы `ps`
--

CREATE TABLE `ps` (
  `pid` int(10) UNSIGNED NOT NULL,
  `sid` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `ps`
--

INSERT INTO `ps` (`pid`, `sid`) VALUES
(2, 1),
(2, 2),
(1, 3),
(3, 3),
(1, 4);

-- --------------------------------------------------------

--
-- Структура таблицы `scales`
--

CREATE TABLE `scales` (
  `id` int(10) UNSIGNED NOT NULL,
  `value` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `scales`
--

INSERT INTO `scales` (`id`, `value`) VALUES
(1, '1:1'),
(2, '1:2'),
(3, '1:3'),
(4, '1:4'),
(5, '1:5'),
(6, '1:6'),
(7, '1:7'),
(8, '1:8'),
(9, '1:9'),
(10, '1:10'),
(11, '1:12'),
(12, '1:14'),
(13, '1:15'),
(14, '1:16'),
(15, '1:18'),
(16, '1:19'),
(17, '1:20'),
(18, '1:21'),
(19, '1:22'),
(20, '1:23'),
(21, '1:24'),
(22, '1:25'),
(23, '1:26'),
(24, '1:27'),
(25, '1:28'),
(26, '1:29'),
(27, '1:30'),
(28, '1:32'),
(29, '1:33'),
(30, '1:34'),
(31, '1:35'),
(32, '1:36'),
(33, '1:37'),
(34, '1:38'),
(35, '1:39'),
(36, '1:40'),
(37, '1:41'),
(38, '1:42'),
(39, '1:43'),
(40, '1:44'),
(41, '1:45'),
(42, '1:46'),
(43, '1:47'),
(44, '1:48'),
(45, '1:50'),
(46, '1:51'),
(47, '1:52'),
(48, '1:53'),
(49, '1:54'),
(50, '1:55'),
(51, '1:56'),
(52, '1:57'),
(53, '1:58'),
(54, '1:59'),
(55, '1:60'),
(56, '1:61'),
(57, '1:62'),
(58, '1:63'),
(59, '1:64'),
(60, '1:65'),
(61, '1:66'),
(62, '1:67'),
(63, '1:68'),
(64, '1:69'),
(65, '1:70'),
(66, '1:72'),
(67, '1:73'),
(68, '1:75'),
(69, '1:76'),
(70, '1:77'),
(71, '1:78'),
(72, '1:80'),
(73, '1:81'),
(74, '1:82'),
(75, '1:83'),
(76, '1:84'),
(77, '1:87'),
(78, '1:88'),
(79, '1:90'),
(80, '1:93'),
(81, '1:95'),
(82, '1:96'),
(83, '1:97'),
(84, '1:100'),
(85, '1:103'),
(86, '1:108'),
(87, '1:110'),
(88, '1:112'),
(89, '1:115'),
(90, '1:120'),
(91, '1:121'),
(92, '1:125'),
(93, '1:126'),
(94, '1:128'),
(95, '1:130'),
(96, '1:140'),
(97, '1:142'),
(98, '1:144'),
(99, '1:145'),
(100, '1:148'),
(101, '1:150'),
(102, '1:160'),
(103, '1:165'),
(104, '1:200'),
(105, '1:220'),
(106, '1:225'),
(107, '1:250'),
(108, '1:285'),
(109, '1:288'),
(110, '1:300'),
(111, '1:350'),
(112, '1:390'),
(113, '1:400'),
(114, '1:426'),
(115, '1:450'),
(116, '1:500'),
(117, '1:530'),
(118, '1:535'),
(119, '1:550'),
(120, '1:570'),
(121, '1:600'),
(122, '1:700'),
(123, '1:720'),
(124, '1:800'),
(125, '1:900'),
(126, '1:1000'),
(127, '1:1100'),
(128, '1:1200'),
(129, '1:1250'),
(130, '1:1500'),
(131, '1:2000'),
(132, '1:2500'),
(133, '1:2700'),
(134, '1:3000');

-- --------------------------------------------------------

--
-- Структура таблицы `sections`
--

CREATE TABLE `sections` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `sections`
--

INSERT INTO `sections` (`id`, `name`, `slug`) VALUES
(1, 'Военные', 'voeinye'),
(2, 'Пожарные', 'pogharnye'),
(3, 'Concept car', 'concept-car'),
(4, 'Автоспорт', 'autosport');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `manufacturers`
--
ALTER TABLE `manufacturers`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `products_manufacturer_id` (`manufacturer_id`),
  ADD KEY `products_scale_id` (`scale_id`);

--
-- Индексы таблицы `ps`
--
ALTER TABLE `ps`
  ADD UNIQUE KEY `pid` (`pid`,`sid`) USING BTREE,
  ADD KEY `product_sections_sid` (`sid`);

--
-- Индексы таблицы `scales`
--
ALTER TABLE `scales`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `manufacturers`
--
ALTER TABLE `manufacturers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `scales`
--
ALTER TABLE `scales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=135;

--
-- AUTO_INCREMENT для таблицы `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_manufacturer_id` FOREIGN KEY (`manufacturer_id`) REFERENCES `manufacturers` (`id`),
  ADD CONSTRAINT `products_scale_id` FOREIGN KEY (`scale_id`) REFERENCES `scales` (`id`);

--
-- Ограничения внешнего ключа таблицы `ps`
--
ALTER TABLE `ps`
  ADD CONSTRAINT `product_sections_pid` FOREIGN KEY (`pid`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `product_sections_sid` FOREIGN KEY (`sid`) REFERENCES `sections` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
