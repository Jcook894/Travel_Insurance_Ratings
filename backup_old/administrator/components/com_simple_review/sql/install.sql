 CREATE TABLE IF NOT EXISTS  #__simplereview_category (
        categoryID int NOT NULL auto_increment,
        templateID int NOT NULL default '-1',
        catOrder INT DEFAULT 0 NOT NULL,
        pageName TEXT default NULL,
        name varchar(30) NOT NULL default '',
        description varchar(255) NOT NULL default '',
        published tinyint(4) NOT NULL default '0',
        categoryImageURL VARCHAR( 255 ),
        userReviews TINYINT DEFAULT '0' NOT NULL,
        lft INT DEFAULT 0 NOT NULL,
        rgt INT DEFAULT 0 NOT NULL,
        INDEX idx_src_lft (lft),
        INDEX idx_src_rgt (rgt),
        PRIMARY KEY  (categoryID)
)ENGINE=MYISAM;

CREATE TABLE IF NOT EXISTS  #__simplereview_review (
        reviewID int NOT NULL auto_increment,
        categoryID int NOT NULL,
        awardID int NOT NULL,
        score DECIMAL(5,1) NOT NULL default '0',
        pageName TEXT default NULL,
        content longtext NOT NULL,
        blurb text default NULL,
        thumbnailURL varchar(255) default NULL,
        imageURL varchar(255) default NULL,
        createdDate timestamp NOT NULL DEFAULT,
        lastModifiedDate timestamp NOT NULL DEFAULT,
        createdByID INT NOT NULL,
        lastModifiedByID INT NOT NULL,
        published tinyint NOT NULL default '0',
        userReview tinyint NOT NULL default '0',
		FULLTEXT KEY idx_srr_blurb_content (blurb,content),
        INDEX idx_srr_categoryID (categoryID),
        INDEX idx_srr_score (score),
        PRIMARY KEY  (reviewID)
)ENGINE=MYISAM;

CREATE TABLE IF NOT EXISTS #__simplereview_category_title (
        categoryTitleID INT UNSIGNED NOT NULL AUTO_INCREMENT ,
        categoryID INT UNSIGNED NOT NULL ,
        titleName TEXT NOT NULL ,
        titleOrder TINYINT UNSIGNED NOT NULL,
		titleType ENUM( 'Text', 'Link', 'Rating', 'List', 'Option', 'Selection') NULL DEFAULT  'Text',
        titleSetup TEXT NULL DEFAULT NULL,
        mandatory TINYINT( 1 ) NOT NULL,
        INDEX idx_srct_categoryID (categoryID),
        FULLTEXT idx_srct_title (titleName),
        INDEX idx_srct_titleOrder (titleOrder),
        PRIMARY KEY ( categoryTitleID )
)ENGINE=MYISAM;

CREATE TABLE IF NOT EXISTS  #__simplereview_review_title (
        reviewTitleID INT UNSIGNED NOT NULL auto_increment,
        categoryTitleID INT UNSIGNED NOT NULL,
        reviewID INT NOT NULL ,
        title TEXT NOT NULL ,
        titleOrder INT NOT NULL,
        titleSetup TEXT NULL DEFAULT NULL,
        FULLTEXT idx_srrt_title (title),
        INDEX idx_srrt_titleOrder (titleOrder),
        INDEX idx_srrt_reviewID (reviewID),
        PRIMARY KEY ( reviewTitleID )
)ENGINE=MYISAM;

 CREATE TABLE IF NOT EXISTS  #__simplereview_comments (
        commentID int(11) NOT NULL auto_increment,
        reviewID int(11) NOT NULL,
        anonymousName varchar(25) NOT NULL default '',
        createdBy varchar(25) NOT NULL default '',
        createdDate timestamp NOT NULL ,
        comment text NOT NULL,
        userRating DECIMAL(5,1) NOT NULL default '0',
        published tinyint(4) NOT NULL default '1',
        avatar VARCHAR( 255 ),
        plainComment TEXT,
        createdByID INT DEFAULT '-1',
        userIP VARCHAR( 25 ),
        PRIMARY KEY  (commentID)
)ENGINE=MYISAM;

CREATE TABLE IF NOT EXISTS  #__simplereview_template (
        templateID INT NOT NULL AUTO_INCREMENT ,
        name VARCHAR( 30 ) NOT NULL ,
        template TEXT NOT NULL ,
        PRIMARY KEY (templateID)
)ENGINE=MYISAM;

CREATE TABLE IF NOT EXISTS  #__simplereview_awards (
        awardID INT NOT NULL AUTO_INCREMENT ,
        name VARCHAR( 30 ) NOT NULL ,
        imageURL VARCHAR( 255 ) NOT NULL ,
        PRIMARY KEY ( awardID )
)ENGINE=MYISAM;

CREATE TABLE IF NOT EXISTS  #__simplereview_banned_ips (
        bannedIP VARCHAR( 25 ) NOT NULL ,
        INDEX ( bannedIP )
)ENGINE=MYISAM;

INSERT INTO #__simplereview_category (name, published, `lft` , `rgt` )
VALUES ('REVIEWCATEGORYROOT', 1, 1, 2);