<?php
/**
 * @package      ITPrism Plugins
 * @subpackage   Buttons
 * @author       Todor Iliev
 * @copyright    Copyright (C) 2010 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      GNU/GPL
 * ITPSocialButtons is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 * ITPSocialButtons Plugin
 *
 * @package		ITPrism Plugins
 * @subpackage	Buttons
 * @since 		1.6
 */
class plgContentITPSocialButtons extends JPlugin {
    
	private $plgUrlPath 	= "";
	private $currentView    = "";
    private $currentTask    = "";
    private $currentOption  = "";
	
	/**
     * Constructor
     *
     * @param object $subject The object to observe
     * @param array  $config  An optional associative array of configuration settings.
     * Recognized key values include 'name', 'group', 'params', 'language'
     * (this list is not meant to be comprehensive).
     */
    public function __construct(&$subject, $config = array()) {
        parent::__construct($subject, $config);
        
        $app =& JFactory::getApplication();
        /** @var $app JSite **/

        if($app->isAdmin()) {
            return;
        }
      
       $this->plgUrlPath 	 =  JURI::root() . "plugins/content/itpsocialbuttons/";
       $this->currentView    =  $app->input->get->getCmd("view");
       $this->currentTask    =  $app->input->get->getCmd("task");
       $this->currentOption  =  $app->input->get->getCmd("option");
    }
    
    /**
     * Prepare the content 
     * There are three places where adds the icons - on the topo, n the bottom and on the both.
     *
     * Method is called by the view and the results are imploded and displayed in a placeholder
     *
     * @param   object      The article object.  Note $article->text is also available
     * @param   object      The article params
     * @param   int         The 'page' number
     * @return  string
     */
    public function onContentPrepare($context, &$article, &$params, $limitstart) {
	
    	if (!$article OR !isset($this->params)) { return; };            
        
        $app = JFactory::getApplication();
        /** @var $app JSite **/

        if($app->isAdmin()) {
            return;
        }
        
        $doc     = JFactory::getDocument();
        /**  @var $doc JDocumentHtml **/
        $docType = $doc->getType();
        
        // Check document type
        if(strcmp("html", $docType) != 0){
            return;
        }
       
        if($this->isRestricted($article, $context)) {
        	return;
        }
       
        if($this->params->get("loadCss")) {
            $doc->addStyleSheet(JURI::root() . "plugins/content/itpsocialbuttons/style.css");
        }
        
        // Loading language file
        JPlugin::loadLanguage('plg_itpsocialbuttons');
        
        // Generate content
        $content = $this->getContent($article, $context);
        $position = $this->params->get('position');
	
        switch($position){
            case 1:
                $article->text = $content . $article->text;
                break;
            case 2:
                $article->text = $article->text . $content;
                break;
            default:
                $article->text = $content . $article->text . $content;
                break;
        }
        
        return true;
    }
    
    private function isRestricted($article, $context) {
    	
    	$result = false;
    	
    	switch($this->currentOption) {
            case "com_content":
            	
            	// It's an implementation of "com_myblog"
            	// I don't know why but $option contains "com_content" for a value
            	// I hope it will be fixed in the future versions of "com_myblog"
            	if(!strcmp($context, "com_myblog") == 0) {
            		if($this->isContentRestricted($article, $context)) {
	                    $result = true;
	                }
	                break;
            	} 
	                
            case "com_myblog":
                
                if($this->isMyBlogRestricted($article, $context)) {
                    $result = true;
                }
                
                break;
                    
            case "com_k2":
                if($this->isK2Restricted($article, $context)) {
                    $result = true;
                }
                break;
                
            case "com_virtuemart":
                if($this->isVirtuemartRestricted($article, $context)) {
                    $result = true;
                }
                break;

            case "com_jevents":
                
                if($this->isJEventsRestricted($article, $context)) {
                    $result = true;
                }
                break;

            case "com_easyblog":
                if($this->isEasyBlogRestricted($article, $context)) {
                    $result = true;
                }
                break;

            case "com_vipportfolio":
                if($this->isVipPortfolioRestricted($article, $context)) {
                    $result = true;
                }
                break;
                
            default:
                $result = true;
                break;   
        }
        
        return $result;
        
    }
    
	/**
     * 
     * Checks allowed articles, exluded categories/articles,... for component COM_CONTENT
     * @param object $article
     * @param string $context
     */
    private function isContentRestricted(&$article, $context) {
        
        // Check for currect context
        if(strpos($context, "com_content") === false) {
           return true;
        }
        
    	/** Check for selected views, which will display the buttons. **/   
        /** If there is a specific set and do not match, return an empty string.**/
        $showInArticles     = $this->params->get('showInArticles');
        if(!$showInArticles AND (strcmp("article", $this->currentView) == 0)){
            return true;
        }
        
        // Will be displayed in view "categories"?
        $showInCategories   = $this->params->get('showInCategories');
        if(!$showInCategories AND (strcmp("category", $this->currentView) == 0)){
            return true;
        }
        
        // Will be displayed in view "featured"?
        $showInFeatured   = $this->params->get('showInFeatured');
        if(!$showInFeatured AND (strcmp("featured", $this->currentView) == 0)){
            return true;
        }
        
        if(
            ($showInCategories AND ($this->currentView == "category") )
        OR 
            ($showInFeatured AND ($this->currentView == "featured") )
            ) {
            $articleData        = $this->getArticle($article);
            $article->id        = JArrayHelper::getValue($articleData,'id');
            $article->catid     = JArrayHelper::getValue($articleData,'catid');
            $article->title     = JArrayHelper::getValue($articleData,'title');
            $article->slug      = JArrayHelper::getValue($articleData, 'slug');
            $article->catslug   = JArrayHelper::getValue($articleData,'catslug');
        }
        
        if(empty($article->id)) {
            return true;            
        }
        
        // Exclude articles
        $excludeArticles = $this->params->get('excludeArticles');
        if(!empty($excludeArticles)){
            $excludeArticles = explode(',', $excludeArticles);
        }
        settype($excludeArticles, 'array');
        JArrayHelper::toInteger($excludeArticles);
        
        // Exluded categories
        $excludedCats           = $this->params->get('excludeCats');
        if(!empty($excludedCats)){
            $excludedCats = explode(',', $excludedCats);
        }
        settype($excludedCats, 'array');
        JArrayHelper::toInteger($excludedCats);
        
        // Included Articles
        $includedArticles = $this->params->get('includeArticles');
        if(!empty($includedArticles)){
            $includedArticles = explode(',', $includedArticles);
        }
        settype($includedArticles, 'array');
        JArrayHelper::toInteger($includedArticles);
        
        if(!in_array($article->id, $includedArticles)) {
            // Check exluded articles
            if(in_array($article->id, $excludeArticles) OR in_array($article->catid, $excludedCats)){
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 
     * This method does verification for K2 restrictions
     * @param jIcalEventRepeat $article
     * @param string $context
     */
    private function isK2Restricted(&$article, $context) {
        
        // Check for currect context
        if(strpos($context, "com_k2") === false) {
           return true;
        }
        
        $displayInArticles     = $this->params->get('k2DisplayInArticles', 0);
        if(!$displayInArticles AND (strcmp("item", $this->currentView) == 0)){
            return true;
        }
        
        $displayInItemlist     = $this->params->get('k2DisplayInItemlist', 0);
        if(!$displayInItemlist AND (strcmp("itemlist", $this->currentView) == 0)){
            return true;
        }
        
        if($article instanceof TableK2Category){
            return true;
        }
        
        return false;
    }
    
    /**
     * 
     * This method does verification for JEvents restrictions
     * @param jIcalEventRepeat $article
     * @param string $context
     */
    private function isJEventsRestricted(&$article, $context) {
        
        // Display buttons only in the description
        if (!is_a($article, "jIcalEventRepeat")) { 
            return true; 
        };
        
        // Check for currect context
        if(strpos($context, "com_jevents") === false) {
           return true;
        }
        
        $displayInEvents     = $this->params->get('jeDisplayInEvents', 0);
        if(!$displayInEvents AND (strcmp("icalrepeat.detail", $this->currentTask) == 0)){
            return true;
        }
        
        return false;
    }
    
    /**
     * 
     * This method does verification for VirtueMart restrictions
     * @param stdClass $article
     * @param string $context
     */
    private function isVirtuemartRestricted(&$article, $context) {
            
        // Check for currect context
        if(strpos($context, "com_virtuemart") === false) {
           return true;
        }

        // Check product details
        $displayInDetails     = $this->params->get('vmDisplayInDetails', 0);
        if(!$displayInDetails AND (strcmp("productdetails", $this->currentView) == 0)){
            return true;
        }
        
        // Check categories
        if(strcmp("category", $this->currentView) == 0){
            return true;
        }
        
        return false;
        
    }
    
	/**
     * 
     * It's a method that verify restriction for the component "com_myblog"
     * @param object $article
     * @param string $context
     */
	private function isMyBlogRestricted(&$article, $context) {

        // Check for currect context
        if(strpos($context, "myblog") === false) {
           return true;
        }
        
        if(!$this->params->get('mbDisplay', 0)){
            return true;
        }
        
        return false;
    }
    
	/**
     * 
     * It's a method that verify restriction for the component "com_myblog"
     * @param object $article
     * @param string $context
     */
	private function isVipPortfolioRestricted(&$article, $context) {

        // Check for currect context
        if(strpos($context, "com_vipportfolio") === false) {
           return true;
        }
        
        return false;
    }
    
	/**
     * 
     * It's a method that verify restriction for the component "com_easyblog"
     * @param object $article
     * @param string $context
     */
	private function isEasyBlogRestricted(&$article, $context) {
        $allowedViews = array("categories", "entry", "latest", "tags");   
        // Check for currect context
        if(strpos($context, "easyblog") === false) {
           return true;
        }
        
        // Only put buttons in allowed views
        if(!in_array($this->currentView, $allowedViews)) {
        	return true;
        }
        
   		// Verify the option for displaying in view "categories"
        $displayInCategories     = $this->params->get('ebDisplayInCategories', 0);
        if(!$displayInCategories AND (strcmp("categories", $this->currentView) == 0)){
            return true;
        }
        
   		// Verify the option for displaying in view "latest"
        $displayInLatest     = $this->params->get('ebDisplayInLatest', 0);
        if(!$displayInLatest AND (strcmp("latest", $this->currentView) == 0)){
            return true;
        }
        
		// Verify the option for displaying in view "entry"
        $displayInEntry     = $this->params->get('ebDisplayInEntry', 0);
        if(!$displayInEntry AND (strcmp("entry", $this->currentView) == 0)){
            return true;
        }
        
	    // Verify the option for displaying in view "tags"
        $displayInTags     = $this->params->get('ebDisplayInTags', 0);
        if(!$displayInTags AND (strcmp("tags", $this->currentView) == 0)){
            return true;
        }
        
        return false;
    }
    
    private function getUrl(&$article, $context) {
        
        $url = JURI::getInstance();
        $uri = "";
        $domain= $url->getScheme() ."://" . $url->getHost();
        
        switch($this->currentOption) {
            case "com_content":
            	
            	// It's an implementation of "com_myblog"
            	// I don't know why but $option contains "com_content" for a value
            	// I hope it will be fixed in the future versions of "com_myblog"
            	if(!strcmp($context, "com_myblog") == 0) {
                	$uri = JRoute::_(ContentHelperRoute::getArticleRoute($article->slug, $article->catslug), false);
                	break;
            	}
            	
            case "com_myblog":
                $uri = $article->permalink;
                break;    
                
                
            case "com_k2":
                $uri = $article->link;
                break;
                
            case "com_virtuemart":
                $uri = $article->link;
                break;
                
            case "com_jevents":
                // Display buttons only in the description
                if (is_a($article, "jIcalEventRepeat")) { 
                    $uri    = $url->getPath();
                };
                
                break;

            case "com_easyblog":
            	$uri	= EasyBlogRouter::getRoutedURL( 'index.php?option=com_easyblog&view=entry&id=' . $article->id , false , false );
                break;

            case "com_vipportfolio":
                $uri = JRoute::_($article->link, false);;
                break;
                    
            default:
                $uri = "";
                break;   
        }
        
        return $domain.$uri;
        
    }
    
    private function getTitle(&$article, $context) {
        
        $title = "";
        
        switch($this->currentOption) {
            case "com_content":
            	
            	// It's an implementation of "com_myblog"
            	// I don't know why but $option contains "com_content" for a value
            	// I hope it will be fixed in the future versions of "com_myblog"
            	if(!strcmp($context, "com_myblog") == 0) {
            		$title= $article->title;
            		break;
            	}
                
            case "com_myblog":
                $title= $article->title;
                break;    
                
            case "com_k2":
                $title= $article->title;
                break;
                
            case "com_virtuemart":
                $title = (!empty($article->custom_title)) ? $article->custom_title : $article->product_name;
                break;
                
            case "com_jevents":
                // Display buttons only in the description
                if (is_a($article, "jIcalEventRepeat")) { 
                    
                    $title    = JString::trim($article->title());
                    if(!$title) {
                        $doc     = JFactory::getDocument();
                        /**  @var $doc JDocumentHtml **/
                        $title    =  $doc->getTitle();
                    }
                };
                
                break;   

            case "com_easyblog":
                $title= $article->title;
                break;
                
            case "com_vipportfolio":
                $title = $article->title;
                break;
                    
            default:
                $title = "";
                break;   
        }
        
        return $title;
        
    }
    
	/**
     * 
     * Load an information about article, if missing, on the view 'category' and 'featured'
     * @param object $article
     */
    private function getArticle(&$article) {
        
        $db = JFactory::getDbo();
        /** @var $db JDatabaseMySQLi **/
        
        $query = "
            SELECT 
                `#__content`.`id`,
                `#__content`.`catid`,
                `#__content`.`alias`,
                `#__content`.`title`,
                `#__categories`.`alias` as category_alias
            FROM
                `#__content`
            INNER JOIN
                `#__categories`
            ON
                `#__content`.`catid`=`#__categories`.`id`
            WHERE
                `#__content`.`introtext` = " . $db->quote($article->text); 
        
        $db->setQuery($query);
        $result = $db->loadAssoc();
        
        if(!empty($result)) {
            $result['slug']     = $result['alias'] ? $result['id'].':'.$result['alias'] : $result['id'];
            $result['catslug']  = $result['category_alias'] ? $result['catid'].':'.$result['category_alias'] : $result['catid'];
        } else {
            $result = array();
        }
        
        return $result;
    }
    
    /**
     * 
     * Generate the HTML code with buttons
     * @param object $article
     */
    private function getContent(&$article, $context){
        
        $url    = rawurlencode( $this->getUrl($article, $context) );
        $title  = rawurlencode( $this->getTitle($article, $context) );
        
        $html 	= '<div class="itp-social-buttons-box">';
        
        if($this->params->get('showTitle')){
            $html .= '<h4>' . $this->params->get('title') . '</h4>';
        }
        
        $html .='<div class="' . $this->params->get('displayLines') . '">';
        $html .= '<div class="' . $this->params->get('displayIcons') . '">';
        
        // Convert the url to short one
        if($this->params->get("shortUrlService")) {
            $url = $this->getShortUrl($url, $this->params);
        }
        
        // Prepare buttons
        if($this->params->get("displayDelicious")) {
            $html .= $this->getDeliciousButton($title, $url);
        }
        if($this->params->get("displayDigg")) {
            $html .= $this->getDiggButton($title, $url);
        }
        if($this->params->get("displayFacebook")) {
            $html .= $this->getFacebookButton($title, $url);
        }
        if($this->params->get("displayGoogle")) {
            $html .= $this->getGoogleButton($title, $url);
        }
        if($this->params->get("displaySumbleUpon")) {
            $html .= $this->getStumbleuponButton($title, $url);
        }
        if($this->params->get("displayTechnorati")) {
            $html .= $this->getTechnoratiButton($title, $url);            
        }
        if($this->params->get("displayTwitter")) {
            $html .= $this->getTwitterButton($title, $url);
        }
        if($this->params->get("displayLinkedIn")) {
            $html .= $this->getLinkedInButton($title, $url);
        }
        
        // Get additional social buttons
        $html .= $this->getExtraButtons($title, $url, $this->params);
        
        $html .= '</div></div></div>';
        
        return $html;
    }
    
	/**
     * A method that make a long url to short url
     * 
     * @param string $link
     * @param array $params
     * @return string
     */
    private function getShortUrl($link, $params){
        
        JLoader::register("ItpShortUrlSocialButtonsPlugin", JPATH_PLUGINS.DS."content".DS."itpsocialbuttons".DS."itpshorturlsocialbuttons.php");
        $options = array(
            "login"     => $params->get("login"),
            "apiKey"    => $params->get("apiKey"),
            "service"   => $params->get("shortUrlService"),
        );
        
        $shortUrl  = new ItpShortUrlSocialButtonsPlugin($link,$options);
        $shortLink = $shortUrl->getUrl();
        if(!$shortLink) {
            // Add logger
            JLog::addLogger(
                array(
                    'text_file' => 'error.php',
                 )
            );
            
            JLog::add($shortUrl->getError(), JLog::ERROR);
        } else {
            $link = $shortLink;
        }
        
        return $link;
            
    }
    
    /**
     * Generate a code for the extra buttons. 
     * Is also replace indicators {URL} and {TITLE} with that of the article.
     * 
     * @param string $title Article Title
     * @param string $url   Article URL
     * @param array $params Plugin parameters
     * 
     * @return string
     */
    private function getExtraButtons($title, $url, &$params) {
        
        $html  = "";
        // Extra buttons
        for($i=1; $i < 6;$i++) {
            $btnName = "ebuttons" . $i;
            $extraButton = $params->get($btnName, "");
            if(!empty($extraButton)) {
                $extraButton = str_replace("{URL}", $url, $extraButton);
                $extraButton = str_replace("{TITLE}", $title, $extraButton);
                $html  .= $extraButton;
            }
        }
        
        return $html;
    }
    
    private function getDeliciousButton($title, $link){
        
        $img_url = $this->plgUrlPath . "images/" . $this->params->get('icons_package') . "/delicious.png";
        
        return '<a href="http://del.icio.us/post?url=' . $link . '&amp;title=' . $title . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Delicious") . '" target="blank" ><img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Delicious") . '" /></a>';
    }
    
    private function getDiggButton($title, $link){
        
        $img_url = $this->plgUrlPath . "images/" . $this->params->get('icons_package') . "/digg.png";
        
        return '<a href="http://digg.com/submit?url=' . $link . '&amp;title=' . $title . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Digg") . '" target="blank" ><img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Digg") . '" /></a>';
    }
    
    private function getFacebookButton($title, $link){
        
        $img_url = $this->plgUrlPath . "images/" . $this->params->get('icons_package') . "/facebook.png";
        
        return '<a href="http://www.facebook.com/sharer.php?u=' . $link . '&amp;t=' . $title . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Facebook") . '" target="blank" ><img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Facebook") . '" /></a>';
    }
    
    private function getGoogleButton($title, $link){
        
        $img_url = $this->plgUrlPath . "images/" . $this->params->get('icons_package') . "/google.png";
        
        return '<a href="http://www.google.com/bookmarks/mark?op=edit&amp;bkmk=' . $link . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Google Bookmarks") . '" target="blank" ><img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Google Bookmarks") . '" /></a>';
    }
    
    private function getStumbleuponButton($title, $link){
        
        $img_url = $this->plgUrlPath . "images/" . $this->params->get('icons_package') . "/stumbleupon.png";
        
        return '<a href="http://www.stumbleupon.com/submit?url=' . $link . '&amp;title=' . $title . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Stumbleupon") . '" target="blank" ><img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Stumbleupon") . '" /></a>';
    }
    
    private function getTechnoratiButton($title, $link){
        
        $img_url = $this->plgUrlPath . "images/" . $this->params->get('icons_package') . "/technorati.png";
        
        return '<a href="http://technorati.com/faves?add=' . $link . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Technorati") . '" target="blank" ><img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Technorati") . '" /></a>';
    }
    
    private function getTwitterButton($title, $link){
        
        $img_url = $this->plgUrlPath . "images/" . $this->params->get('icons_package') . "/twitter.png";
        
        return '<a href="http://twitter.com/share?text=' . $title . "&amp;url=" . $link . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Twitter") . '" target="blank" ><img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Twitter") . '" /></a>';
    }
    
    private function getLinkedInButton($title, $link){
        
        $img_url = $this->plgUrlPath . "images/" . $this->params->get('icons_package') . "/linkedin.png";
        
        return '<a href="http://www.linkedin.com/shareArticle?mini=true&amp;url=' . $link .'&amp;title=' . $title . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "LinkedIn") . '" target="blank" ><img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "LinkedIn") . '" /></a>';
    }
}
