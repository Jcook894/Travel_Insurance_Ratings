<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2015 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit https://www.jreviews.com
 * or contact sales@jreviews.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class EverywhereController extends MyController {

    var $uses = array('menu','user','listing_total','review','field','criteria','vote','media');

    var $helpers = array('assets','routes','libraries','html','form','time','jreviews','custom_fields','rating','community','widgets','media');

    var $components = array('config','access','everywhere','media_storage');

    var $autoRender = false;

    var $autoLayout = false;

    var $review_fields = null;

    var $formTokenKeys = array('id'=>'review_id','pid'=>'listing_id','mode'=>'extension','criteria_id'=>'criteria_id');

    function beforeFilter()
    {
        # Call beforeFilter of MyController parent class
        parent::beforeFilter();
    }

    function afterFilter()
    {
        $Assets = ClassRegistry::getClass('AssetsHelper');

        if(isset($this->review_fields))
        {
            $Assets->assetParams['review_fields'] = $this->review_fields;
        }

        unset($this->review_fields);

        isset($this->owner_id) and $Assets->assetParams['owner_id'] = $this->owner_id;

        parent::afterFilter();
    }

    /**
     * Method used in Everywhere extensions detail pages
     *
     * @return array with html output, listing, reviews, rating summary
     */
    function index()
    {
        $this->captcha = $this->Access->showCaptcha();

        $listing_id = Sanitize::getInt($this->data,'listing_id');

        $listing = $this->Listing->findRow(array('conditions'=>"Listing.{$this->Listing->realKey} = $listing_id"));

        if(!is_array($listing) || empty($listing)) {

            return false;
        }

        if(!is_array($listing['Criteria']['required'])) {

            $listing['Criteria']['required'] = explode("\n",$listing['Criteria']['required']);
        }

        # Override global configuration
        isset($listing['ListingType']) and $this->Config->override($listing['ListingType']['config']);

        $extension = isset($this->Listing->extension_alias) ? $this->Listing->extension_alias : $this->Listing->extension;

        $conditions = array(
            'Review.pid= '. $listing_id,
            "Review.mode = " . $this->Quote($extension),
            'Review.published = 1',
            'Review.author = 0',
            "JreviewsCategory.`option` = " . $this->Quote($extension)
        );

        $this->limit = Sanitize::getInt($this->data,'limit_special',$this->Config->user_limit);

        $queryData = array(
            'conditions'=>$conditions,
            'offset'=>0,
            'limit'=>$this->limit,
            'group'=>array('Review.id'),
            'order'=>array('Review.created DESC')
        );

        $reviews = $this->Review->findAll($queryData);

        $review_count = $listing['Review']['review_count'];

        $review_fields = $this->review_fields = $this->Field->getFieldsArrayNew($listing['Criteria']['criteria_id'], 'review');

        # Get current listing review count for logged in user

        $listing['User']['user_review_count'] = $this->_user->id == 0 ? 0 : $this->Review->findCount(array(
                'conditions'=>array(
                    'Review.pid = '.$listing_id,
                    "Review.userid = " . (int) $this->_user->id,
                    "Review.mode = " . $this->Quote($listing['Listing']['extension']),
                    "Review.published >= 0",
                    "Review.author = 0"
                )));

        # check for duplicate reviews

        $listing['User']['duplicate_review'] = false;

        // It's a guest so we only care about checking the IP address if this feature is not disabled and
        // server is not localhost

        if(!$this->_user->id)
        {
            if(!$this->Config->review_ipcheck_disable && $this->ipaddress != '127.0.0.1' && $this->ipaddress != '::1')
            {
                // Do the ip address check everywhere except in localhost
               $listing['User']['duplicate_review'] = (bool) $this->Review->findCount(array(
				   'conditions'=>array(
						'Review.pid = '.$listing_id,
						"Review.ipaddress = '{$this->ipaddress}'",
						"Review.mode = '{$extension}'",
						"Review.author = 0",
						"Review.published >= 0"
					),
					'session_cache'=>false
				));
            }
        }
        else
        // It's a registered user and multiple reviews not allowed
        {
            if(!$this->Config->user_multiple_reviews)
            {
                $listing['User']['duplicate_review'] = (bool) $this->Review->findCount(array(
					'conditions'=>array(
						'Review.pid = '.$listing_id,
						"(Review.userid = {$this->_user->id}" .
							(
								$this->ipaddress != '127.0.0.1' && $this->ipaddress != '::1' && !$this->Config->review_ipcheck_disable
							?
								" OR Review.ipaddress = '{$this->ipaddress}') "
							:
								')'
							),
						"Review.mode = '{$extension}'",
						"Review.author = 0",
						"Review.published >= 0"
					),
				    'session_cache'=>false
				));
            }
        }

        $this->set(array(
                'User'=>$this->_user,
                'listing'=>$listing,
                'review'=>$listing,
                'reviews'=>$reviews,
                'extension'=>$listing['Listing']['extension'],
                'review_count'=>$review_count,
                'review_fields'=>$review_fields,
                'formTokenKeys'=>$this->formTokenKeys
            )
        );

        if(!class_exists('RatingHelper')) {

            S2App::import('Helper','rating','jreviews');
        }

        $Rating = ClassRegistry::getClass('RatingHelper');

        // Mar 9, 2017 - Fixed Reviews of Me error because the Config class is not available in the ratings helper
        $Rating->Config = $this->Config;

        $output = array(
            'output'=>$this->render($this->name,'reviews'),
            'summary'=>$Rating->overallRatings($listing,'content','user'),
            'detailed_ratings'=>!empty($reviews) ? $Rating->detailedRatings($listing,'user') : '', // Ratings graph
            'listing'=>$listing,
            'reviews'=>$reviews,
            'review_count'=>$review_count,
            'average_rating'=>$Rating->round(Sanitize::getVar($listing['RatingUser'],'average_rating'),$this->Config->rating_scale)
        );

        return $output;
    }

    /**
     * Method used in Everywhere extensions category pages
     *
     * @return array with html output, rating summary
     */
    function category()
    {
        $listing_id = $this->data['listing_id'];

        $extension = $this->data['extension'];

        $listing = $this->Listing->findRow(array('conditions'=>"Listing.{$this->Listing->realKey} = " . (int) $listing_id));

        if(!is_array($listing) || empty($listing)) {
            return false;
        }

        $conditions = array(
            'Review.pid= '. (int) $listing_id,
            'Review.author = 0',
            'Review.published = 1',
            "Review.mode = " . $this->Quote($this->data['extension']),
        );

        $queryData = array(
            'conditions'=>$conditions
        );

        // Remove unnecessary query parameters for findCount
        $this->Review->joins = array(); // Only need to query comments table

        $review_count = $this->Review->findCount($queryData);

        // prepare ratings_summary array

        $query = "
            SELECT
                user_rating, user_criteria_rating, user_rating_count, user_criteria_rating_count
            FROM
                #__jreviews_listing_totals
            WHERE
                listing_id = " . (int) $listing_id . "
                AND extension = " . $this->Quote($extension)
        ;

        $totals = $this->ListingTotal->query($query,'loadAssoc');

        $ratings_summary = array(
            'Rating' => array(
                'average_rating' => $totals['user_rating'] > 0 ? $totals['user_rating'] : false,
                'ratings' => explode(',', $totals['user_criteria_rating']),
                'criteria_rating_count' => explode(',', $totals['user_criteria_rating_count'])
            ),
            'Criteria' => $listing['Criteria'],
            'summary' => 1
        );

        # Initialize review array and set Criteria and extension keys
        $review = $this->Review->init();

        $review['Criteria'] = $listing['Criteria'];

        $review['Review']['extension'] = $extension;

        $review = array_merge($review,$ratings_summary);

        // Make sure that detailed rating is processed as such in category page independent of detailed rating setting in reviews tab
        unset($review['Review']);

        $this->Config->user_rating = 1;

        if(!class_exists('RatingHelper'))  {
            S2App::import('Helper','rating','jreviews');
        }

        $Rating = ClassRegistry::getClass('RatingHelper');

        $Rating->Config = $this->Config;

        $Rating->Config->author_review = 0;

        $output = $Rating->overallRatings($listing,'list','user');

        $this->set(array(
                'reviewType'=>'user',
                'review'=>$review,
                'ratings_summary'=>$ratings_summary,
                'review_count'=>$review_count,
                'user_rating_count'=>$totals['user_rating_count'],
            )
        );

        return array(
            'output'=>$output,
            'listing'=>$listing,
            'review_count'=>$review_count,
            'detailed_ratings'=>$Rating->detailedRatings($review,'user'), // Ratings graph
            'ratings'=>$ratings_summary // Ratings array
        );
    }

}

