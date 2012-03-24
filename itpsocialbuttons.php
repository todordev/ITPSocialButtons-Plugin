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
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

/**
 * ITPSocialButtons Plugin
 *
 * @package		ITPrism Plugins
 * @subpackage	Buttons
 * @since 		1.5
 */
class plgContentITPSocialButtons extends JPlugin {
    
	private $plgUrlPath 	= "";
	private $currentView    = "";
    private $currentTask    = "";
    private $currentOption  = "";
    
    public function __construct($subject, $params){
        
        parent::__construct($subject, $params);
    
        $app =& JFactory::getApplication();
        /* @var $app JApplication */

        if($app->isAdmin()) {
            return;
        }
      
       $this->plgUrlPath 	 =  JURI::root() . "plugins/content/itpsocialbuttons/";
       $this->currentView    =  JRequest::getCmd("view");
       $this->currentTask    =  JRequest::getCmd("task");
       $this->currentOption  =  JRequest::getCmd("option");
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
    public function onPrepareContent(&$article, &$params, $limitstart){

    	if (!$article OR !isset($this->params) ) { return; };     
        
        $app =& JFactory::getApplication();
        /** @var $app JApplication **/

        if($app->isAdmin()) {
            return;
        }
        
        $doc   = JFactory::getDocument();
        /** @var $doc JDocumentHtml **/
        $docType = $doc->getType();
        
        // Check document type
        if(strcmp("html", $docType) != 0){
            return;
        }
        
        // Generate context value
        $context = $this->currentOption.".".$this->currentView;
        
        switch($this->currentOption) {
            case "com_content":
                if($this->isContentRestricted($article, $context)) {
                    return;
                }
                break;    
                
            case "com_k2":
                if($this->isK2Restricted($article, $context)) {
                    return;
                }
                break;
                
            case "com_virtuemart":
                if($this->isVirtuemartRestricted($article, $context)) {
                    return;
                }
                break;

            case "com_jevents":
                if($this->isJEventsRestricted($article, $context)) {
                    return;
                }
                break;
                
            default:
                return;
                break;   
        }
        
        if($this->params->get("loadCss")) {
            $doc->addStyleSheet(JURI::root() . "plugins/content/itpsocialbuttons/style.css");
        }
        
        /*** Loading language file ***/
        JPlugin::loadLanguage('plg_itpsocialbuttons',JPATH_ADMINISTRATOR);
        
        /*** Generate content ***/
        $content = $this->getContent($article);
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
        
    }
    
	/**
     * 
     * Checks allowed articles, exluded categories/articles,... for component COM_CONTENT
     * @param object $article
     */
    private function isContentRestricted($article) {
        
    	// Check for valid article properties
    	if(!$article->id) {return true;}
    	
    	// Check where we are able to show buttons?
        $showInArticles     = $this->params->get('showInArticles');
        $showInCategories   = $this->params->get('showInCategories');
        $showInSections     = $this->params->get('showInSections');
        $showInFrontPage    = $this->params->get('showInFrontPage');
        
        /** Check for selected views, which will display the buttons. **/   
        /** If there is a specific set and do not match, return an empty string.**/
        if(!$showInArticles AND (strcmp("article", $this->currentView) == 0)){
            return true;
        }
        
        if(!$showInCategories AND (strcmp("category", $this->currentView) == 0)){
            return true;
        }
        
        if(!$showInSections AND (strcmp("section", $this->currentView) == 0)){
            return true;
        }
        
        if(!$showInFrontPage AND (strcmp("frontpage", $this->currentView) == 0)){
            return true;
        }
        
        // Exclude categories
        $excludedCats = $this->params->get('excludeCats');
        if(!empty($excludedCats)){
            $excludedCats = explode(',', $excludedCats);
        }
        settype($excludedCats, 'array');
        JArrayHelper::toInteger($excludedCats);
        
        // Exclude sections
        $excludeSections = $this->params->get('excludeSections');
        if(!empty($excludeSections)){
            $excludeSections = explode(',', $excludeSections);
        }
        settype($excludeSections, 'array');
        JArrayHelper::toInteger($excludeSections);
        
        // Exclude articles
        $excludeArticles = $this->params->get('excludeArticles');
        if(!empty($excludeArticles)){
            $excludeArticles = explode(',', $excludeArticles);
        }
        settype($excludeArticles, 'array');
        JArrayHelper::toInteger($excludeArticles);
        
        // Included Articles
        $includedArticles = $this->params->get('includeArticles');
        if(!empty($includedArticles)){
            $includedArticles = explode(',', $includedArticles);
        }
        settype($includedArticles, 'array');
        JArrayHelper::toInteger($includedArticles);
        
        if(!in_array($article->id, $includedArticles)) {
            // Check exluded places
            if(in_array($article->catid, $excludedCats) OR in_array($article->sectionid, $excludeSections) OR in_array($article->id, $excludeArticles)){
                return true;
            }
        }
        
        return false;
    }
    
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
        
    }
    
	/**
     * 
     * Do verifications for JEvent extension
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
        
    }
    
    private function isVirtuemartRestricted(&$article, $context) {
            
        // Check for currect context
        if(strpos($context, "com_virtuemart") === false) {
           return true;
        }
        
        $displayInDetails     = $this->params->get('vmDisplayInDetails', 0);
        if(!$displayInDetails AND (strcmp("productdetails", $this->currentView) == 0)){
            return true;
        }
    }
    
	private function getUrl(&$article) {
        
        $url = JURI::getInstance();
        $uri = "";
        $domain= $url->getScheme() ."://" . $url->getHost();
        
        switch($this->currentOption) {
            case "com_content":
                $uri = JRoute::_(ContentHelperRoute::getArticleRoute($article->slug, $article->catslug, $article->sectionid), false);
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
                
            default:
                $uri = "";
                break;   
        }
        
        return $domain.$uri;
        
    }
    
    private function getTitle(&$article) {
        
        switch($this->currentOption) {
            case "com_content":
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
                
            default:
                $title = "";
                break;   
        }
        
        return htmlentities($title, ENT_QUOTES, "UTF-8");
        
    }
    
    private function getContent(&$article){
        
        $url  = rawurlencode( $this->getUrl($article) );
        $title= rawurlencode( $this->getTitle($article) );
        
        $html 	= '<div class="itp-social-buttons-box">';
        
        if($this->params->get('showTitle')){
            $html .= '<h4>' . $this->params->get('title') . '</h4>';
        }
        
        $html .='<div class="' . $this->params->get('displayLines') . '">';
        $html .= '<div class="' . $this->params->get('displayIcons') . '">';
        
        // Short URL service
        if($this->params->get("shortUrlService")) {
            $url = $this->getShortUrl($url, $this->params);
        }
        
        // Get social buttons
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
        
        // Get extra social buttons
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
        
        JLoader::register("ItpShortUrlSocialButtons",JPATH_PLUGINS.DS."content".DS."itpsocialbuttons".DS."itpshorturlsocialbuttons.php");
        $options = array(
            "login"     => $params->get("login"),
            "apiKey"    => $params->get("apiKey"),
            "service"   => $params->get("shortUrlService"),
        );
        $shortUrl = new ItpShortUrlSocialButtons($link,$options);
        $shortLink = $shortUrl->getUrl();
        if(!$shortLink) {
            jimport( 'joomla.error.log' );
            // get an instance of JLog for myerrors log file
            $log = JLog::getInstance();
            // create entry array
            $entry = array(
                'LEVEL' => '1',
                'STATUS' => "ITPSocialButtons",
                'COMMENT' => $shortUrl->getError()
            );
            // add entry to the log
            $log->addEntry($entry);
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
                $extraButton = str_replace("{URL}", $url,$extraButton);
                $extraButton = str_replace("{TITLE}", $title,$extraButton);
                $html  .= $extraButton;
            }
        }
        
        return $html;
    }
    
    private function getDeliciousButton($title, $link){
        
        $img_url = $this->plgUrlPath . "images/" . $this->params->get('style') . "/delicious.png";
        
        return '<a href="http://del.icio.us/post?url=' . $link . '&amp;title=' . $title . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Delicious") . '" target="_blank" >
		<img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Delicious") . '" />
		</a>';
    
    }
    
    private function getDiggButton($title, $link){
        
        $img_url = $this->plgUrlPath . "images/" . $this->params->get('style') . "/digg.png";
        
        return '<a href="http://digg.com/submit?url=' . $link . '&amp;title=' . $title . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Digg") . '" target="_blank" >
        <img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Digg") . '" />
        </a>';
    
    }
    
    private function getFacebookButton($title, $link){
        
        $img_url = $this->plgUrlPath . "images/" . $this->params->get('style') . "/facebook.png";
        
        return '<a href="http://www.facebook.com/sharer.php?u=' . $link . '&amp;t=' . $title . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Facebook") . '" target="_blank" >
        <img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Facebook") . '" />
        </a>';
    
    }
    
    private function getGoogleButton($title, $link){
        
        $img_url = $this->plgUrlPath . "images/" . $this->params->get('style') . "/google.png";
        
        return '<a href="http://www.google.com/bookmarks/mark?op=edit&amp;bkmk=' . $link . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Google Bookmarks") . '" target="_blank" >
        <img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Google Bookmarks") . '" />
        </a>';
    
    }
    
    private function getStumbleuponButton($title, $link){
        
        $img_url = $this->plgUrlPath . "images/" . $this->params->get('style') . "/stumbleupon.png";
        
        return '<a href="http://www.stumbleupon.com/submit?url=' . $link . '&amp;title=' . $title . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Stumbleupon") . '" target="_blank" >
        <img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Stumbleupon") . '" />
        </a>';
    
    }
    
    private function getTechnoratiButton($title, $link){
        
        $img_url = $this->plgUrlPath . "images/" . $this->params->get('style') . "/technorati.png";
        
        return '<a href="http://technorati.com/faves?add=' . $link . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Technorati") . '" target="_blank" >
        <img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Technorati") . '" />
        </a>';
    
    }
    
    private function getTwitterButton($title, $link){
        
        $img_url = $this->plgUrlPath . "images/" . $this->params->get('style') . "/twitter.png";
        
        return '<a href="http://twitter.com/share?text=' . $title . "&amp;url=" . $link . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Twitter") . '" target="_blank" >
        <img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Twitter") . '" />
        </a>';
    
    }
    
    private function getLinkedInButton($title, $link){
        
        $img_url = $this->plgUrlPath . "images/" . $this->params->get('style') . "/linkedin.png";
        
        return '<a href="http://www.linkedin.com/shareArticle?mini=true&amp;url=' . $link .'&amp;title=' . $title . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "LinkedIn") . '" target="_blank" >
        <img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "LinkedIn") . '" />
        </a>';
    
    }

}
