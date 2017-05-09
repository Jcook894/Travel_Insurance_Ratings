DROP TABLE IF EXISTS #__simplereview_review_title;
DROP TABLE IF EXISTS #__simplereview_category_title;

DROP TABLE IF EXISTS #__simplereview;
DROP TABLE IF EXISTS #__simplereview_review;
DROP TABLE IF EXISTS #__simplereview_category;

DROP TABLE IF EXISTS #__simplereview_template;
DROP TABLE IF EXISTS #__simplereview_comments;
DROP TABLE IF EXISTS #__simplereview_awards;
DROP TABLE IF EXISTS #__simplereview_banned_ips;

DELETE FROM #__components WHERE #__components.option='com_simple_review';