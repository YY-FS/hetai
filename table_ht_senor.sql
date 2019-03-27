create table ht_sensor(
    `id` int(11) not null auto_increment primary key,
    `machine` tinyint(2) not null DEFAULT 1 COMMENT '机器',
    `temperature` varchar(20) not null DEFAULT '' COMMENT '温度',
    `humidity` varchar(20) not null DEFAULT '' COMMENT '湿度',
    `pressure` varchar(20) not null DEFAULT '' COMMENT '压力',
    `airquality` varchar(20) not null DEFAULT '' COMMENT '空气质量',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP COMMENT '采集时间'
)ENGINE=Innodb DEFAULT CHARSET=utf8 COMMENT='合泰杯大创建表语句';
GRANT ALL PRIVILEGES ON *.* TO 'yyfs'@'%'IDENTIFIED BY '19yaoyifan49@' WITH GRANT OPTION;