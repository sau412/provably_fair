SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE `ex_currencies` (
  `uid` int(11) NOT NULL,
  `symbol` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `coingecko_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `rate` double NOT NULL,
  `balance` decimal(16,8) NOT NULL,
  `withdraw_fee` decimal(16,8) NOT NULL,
  `min_send_amount` decimal(16,8) NOT NULL,
  `wallet_api` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `wallet_key` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `ex_exchanges` (
  `uid` int(11) NOT NULL,
  `user_uid` int(11) NOT NULL,
  `from_currency_uid` int(11) NOT NULL,
  `from_amount` decimal(16,8) NOT NULL,
  `rate` double NOT NULL,
  `to_currency_uid` int(11) NOT NULL,
  `to_amount` decimal(16,8) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `ex_stats` (
`symbol` varchar(10)
,`sum_from_amount` decimal(38,8)
,`sum_to_amount` decimal(38,8)
);

CREATE TABLE `ex_transactions` (
  `uid` bigint(20) NOT NULL,
  `user_uid` bigint(20) DEFAULT NULL,
  `currency_uid` int(11) NOT NULL,
  `amount` decimal(16,8) NOT NULL,
  `fee` decimal(16,8) NOT NULL DEFAULT '0.00000000',
  `address` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `status` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `wallet_uid` int(11) DEFAULT NULL,
  `tx_id` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `confirmations` int(11) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `ex_wallets` (
  `uid` int(11) NOT NULL,
  `user_uid` int(11) NOT NULL,
  `currency_uid` int(11) NOT NULL,
  `balance` decimal(16,8) NOT NULL DEFAULT '0.00000000',
  `wallet_uid` int(11) DEFAULT NULL,
  `deposit_address` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `ip_rolls` (
  `uid` int(11) NOT NULL,
  `ip` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `log` (
  `uid` bigint(20) NOT NULL,
  `user_uid` bigint(20) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `lottery_rewards` (
  `uid` int(11) NOT NULL,
  `place` int(11) NOT NULL,
  `percentage` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `lottery_rounds` (
  `uid` int(11) NOT NULL,
  `seed` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `stop` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `lottery_tickets` (
  `uid` int(11) NOT NULL,
  `round_uid` int(11) NOT NULL,
  `user_uid` int(11) NOT NULL,
  `spent` decimal(16,8) NOT NULL,
  `tickets` int(11) NOT NULL,
  `user_seed` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `best_hash` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reward` decimal(16,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `rewards` (
  `uid` int(11) NOT NULL,
  `roll_min` int(11) NOT NULL,
  `roll_max` int(11) NOT NULL,
  `reward` decimal(16,8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `rolls` (
  `uid` int(11) NOT NULL,
  `user_uid` int(11) NOT NULL,
  `roll_type` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `server_seed` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `user_seed` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `roll_result` int(11) DEFAULT NULL,
  `bet` decimal(16,8) NOT NULL,
  `profit` decimal(16,8) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `sessions` (
  `uid` bigint(20) NOT NULL,
  `user_uid` int(11) DEFAULT NULL,
  `session` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `captcha` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `transactions` (
  `uid` bigint(20) NOT NULL,
  `user_uid` bigint(20) DEFAULT NULL,
  `amount` decimal(16,8) NOT NULL,
  `address` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `status` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `wallet_uid` int(11) DEFAULT NULL,
  `tx_id` varchar(100) COLLATE utf8_unicode_ci DEFAULT '',
  `confirmations` int(11) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `users` (
  `uid` bigint(20) NOT NULL,
  `mail` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `login` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `salt` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `password_hash` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `deposited` decimal(16,8) NOT NULL DEFAULT '0.00000000',
  `balance` decimal(16,8) NOT NULL DEFAULT '0.00000000',
  `register_time` datetime NOT NULL,
  `login_time` datetime NOT NULL,
  `last_roll_time` datetime DEFAULT NULL,
  `withdraw_address` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_admin` int(11) NOT NULL DEFAULT '0',
  `server_seed` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `user_seed` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `wallet_uid` int(11) DEFAULT NULL,
  `deposit_address` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `user_variables` (
  `uid` int(11) NOT NULL,
  `user_uid` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `variables` (
  `uid` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `variables` (`uid`, `name`, `value`, `timestamp`) VALUES
(1, 'login_enabled', '1', '2019-01-31 09:28:04'),
(2, 'payouts_enabled', '1', '2019-10-30 09:05:34'),
(3, 'api_enabled', '0', '2018-12-05 06:00:17'),
(4, 'global_message', '', '2021-01-27 19:15:49'),
(5, 'info', '', '2021-03-22 14:05:53'),
(6, 'bet_lo_limit', '4750', '2019-01-31 13:06:27'),
(7, 'bet_hi_limit', '5250', '2019-01-31 13:06:32'),
(8, 'bet_min', '0.001', '2019-01-31 13:40:57'),
(9, 'bet_max', '100', '2019-01-31 13:44:00'),
(10, 'withdraw_min', '0.00000001', '2019-07-26 11:57:53'),
(11, 'wallet_balance', '0', '2021-08-24 11:10:02'),
(33776, 'total_users', '0', '2021-08-24 06:20:01'),
(33777, 'active_users', '0', '2021-08-24 09:05:03'),
(33778, 'users_balance', '0', '2021-08-24 11:40:03'),
(33779, 'free_rolls', '0', '2021-08-24 11:41:10'),
(33780, 'bet_rolls', '0', '2021-08-24 11:07:36'),
(33781, 'pay_rolls', '0', '2021-08-24 09:01:18'),
(33782, 'lottery_tickets', '0', '2021-08-24 11:40:03'),
(33783, 'lottery_funds', '0', '2021-08-24 09:45:02');
DROP TABLE IF EXISTS `ex_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ex_stats`  AS  select `c`.`symbol` AS `symbol`,`s_from`.`sum_from_amount` AS `sum_from_amount`,`s_to`.`sum_to_amount` AS `sum_to_amount` from ((`ex_currencies` `c` join (select `ex_exchanges`.`from_currency_uid` AS `from_currency_uid`,sum(`ex_exchanges`.`from_amount`) AS `sum_from_amount` from `ex_exchanges` group by `ex_exchanges`.`from_currency_uid`) `s_from` on((`s_from`.`from_currency_uid` = `c`.`uid`))) join (select `ex_exchanges`.`to_currency_uid` AS `to_currency_uid`,sum(`ex_exchanges`.`to_amount`) AS `sum_to_amount` from `ex_exchanges` group by `ex_exchanges`.`to_currency_uid`) `s_to` on((`s_to`.`to_currency_uid` = `c`.`uid`))) ;


ALTER TABLE `ex_currencies`
  ADD PRIMARY KEY (`uid`);

ALTER TABLE `ex_exchanges`
  ADD PRIMARY KEY (`uid`);

ALTER TABLE `ex_transactions`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `user_uid` (`user_uid`),
  ADD KEY `tx_id` (`tx_id`,`address`,`user_uid`) USING BTREE,
  ADD KEY `wallet_uid` (`wallet_uid`) USING BTREE,
  ADD KEY `status` (`status`);

ALTER TABLE `ex_wallets`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `user_uid` (`user_uid`,`currency_uid`);

ALTER TABLE `ip_rolls`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `ip` (`ip`);

ALTER TABLE `log`
  ADD PRIMARY KEY (`uid`);

ALTER TABLE `lottery_rewards`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `place` (`place`);

ALTER TABLE `lottery_rounds`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `stop` (`stop`);

ALTER TABLE `lottery_tickets`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `round_uid` (`round_uid`,`user_uid`),
  ADD KEY `best_hash` (`best_hash`),
  ADD KEY `user_uid` (`user_uid`,`reward`);

ALTER TABLE `rewards`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `roll_min` (`roll_min`);

ALTER TABLE `rolls`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `user_uid` (`user_uid`),
  ADD KEY `roll_type` (`roll_type`),
  ADD KEY `timestamp` (`timestamp`);

ALTER TABLE `sessions`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `user_uid_session` (`session`,`user_uid`) USING BTREE;

ALTER TABLE `transactions`
  ADD PRIMARY KEY (`uid`),
  ADD KEY `user_uid` (`user_uid`),
  ADD KEY `tx_id` (`tx_id`,`address`,`user_uid`) USING BTREE,
  ADD KEY `wallet_uid` (`wallet_uid`) USING BTREE,
  ADD KEY `status` (`status`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `login` (`login`),
  ADD KEY `mail` (`mail`),
  ADD KEY `deposit_address` (`deposit_address`),
  ADD KEY `wallet_uid` (`wallet_uid`);

ALTER TABLE `user_variables`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `user_uid` (`user_uid`,`name`) USING BTREE;

ALTER TABLE `variables`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `name` (`name`);


ALTER TABLE `ex_currencies`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `ex_exchanges`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `ex_transactions`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `ex_wallets`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `ip_rolls`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `log`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `lottery_rewards`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `lottery_rounds`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `lottery_tickets`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `rewards`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `rolls`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `sessions`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `transactions`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users`
  MODIFY `uid` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `user_variables`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `variables`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;