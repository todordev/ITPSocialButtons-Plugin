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

define('ITP_SOCIAL_BUTTONS_URL', JURI::base() . "plugins/content/itpsocialbuttons/");

/**
 * ITPSocialButtons Plugin
 *
 * @package		ITPrism Plugins
 * @subpackage	Buttons
 * @since 		1.6
 */
class plgContentITPSocialButtons extends JPlugin {
    
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

        $app =& JFactory::getApplication();
        /* @var $app JApplication */

        // Do not render the buttons in administration area
        if($app->isAdmin()) {
            return;
        }
        
        $doc   = JFactory::getDocument();
        /* @var $doc JDocumentHtml */
        $docType = $doc->getType();
        
        // Joomla! must render content of this plugin only in HTML document
        if(strcmp("html", $docType) != 0){
            return;
        }
        
        $currentOption = JRequest::getCmd("option");
        
        if(($currentOption != "com_content") OR !isset($article) OR empty($article->id) OR !isset($this->params)) {
            return;            
        }
        
        JPlugin::loadLanguage('plg_itpsocialbuttons');
        
        $buttons = $this->getButtons($article);
        $position = $this->params->get('position');
        
        switch($position){
            case 1:
                $article->text = $buttons . $article->text;
                break;
            case 2:
                $article->text = $article->text . $buttons;
                break;
            default:
                $article->text = $buttons . $article->text . $buttons;
                break;
        }
        
        return true;
    }
    
    private function getButtons(&$article){
        
        $view        = $this->params->get('view');
        $currentView = JRequest::getWord("view");
        
        // Check where we are able to show buttons?
        $showInArticles     = $this->params->get('showInArticles');
        
        /** Check for selected views, which will display the buttons. **/   
        /** If there is a specific set and do not match, return an empty string.**/
        if($showInArticles AND (strcmp("article", $currentView) != 0)){
            return "";
        }
        
        $excludedCats = $this->params->get('excludeCats');
        if(!empty($excludedCats)){
            $excludedCats = explode(',', $excludedCats);
        }
        settype($excludedCats, 'array');
        JArrayHelper::toInteger($excludedCats);
        
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
            // Check exluded views
            if(in_array($article->catid, $excludedCats) OR in_array($article->id, $excludeArticles)){
                return "";
            }
        }
        
        $html = "";
        
        $style = ITP_SOCIAL_BUTTONS_URL . "style.css";
        $doc   = JFactory::getDocument();
        /* @var $doc JDocumentHtml */
        $doc->addStyleSheet($style);
        
        $html .= '<div class="itp-social-buttons-box">';
        
        if($this->params->get('showTitle')){
            $html .= '<h4>' . $this->params->get('title') . '</h4>';
        }
        
        $html .='<div class="' . $this->params->get('displayLines') . '">';
        $html .= '<div class="' . $this->params->get('displayIcons') . '">';
        
        
        $url = JURI::base();
        $url = new JURI($url);
        $root= $url->getScheme() ."://" . $url->getHost();
        
        $link = JRoute::_(ContentHelperRoute::getArticleRoute($article->slug, $article->catslug), false);
        $link = $root.$link;
        
        $title      = rawurlencode($article->title);
        $link       = rawurlencode($link);
        
        // Short URL service
        if($this->params->get("shortUrlService")) {
            $link = $this->getShortUrl($link, $this->params);
        }
        
        if($this->params->get("displayDelicious")) {
            $html .= $this->getDeliciousButton($title, $link);
        }
        if($this->params->get("displayDigg")) {
            $html .= $this->getDiggButton($title, $link);
        }
        if($this->params->get("displayFacebook")) {
            $html .= $this->getFacebookButton($title, $link);
        }
        if($this->params->get("displayGoogle")) {
            $html .= $this->getGoogleButton($title, $link);
        }
        if($this->params->get("displaySumbleUpon")) {
            $html .= $this->getStumbleuponButton($title, $link);
        }
        if($this->params->get("displayTechnorati")) {
            $html .= $this->getTechnoratiButton($title, $link);            
        }
        if($this->params->get("displayTwitter")) {
            $html .= $this->getTwitterButton($title, $link);
        }
        if($this->params->get("displayLinkedIn")) {
            $html .= $this->getLinkedInButton($title, $link);
        }
        
        // Get extra social buttons
        $html .= $this->getExtraButtons($title, $link, $this->params);
        
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
        
        $img_url = ITP_SOCIAL_BUTTONS_URL . "images/" . $this->params->get('style') . "/delicious.png";
        
        return '<a href="http://del.icio.us/post?url=' . $link . '&amp;title=' . $title . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Delicious") . '" target="blank" >
		<img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Delicious") . '" />
		</a>';
    
    }
    
    private function getDiggButton($title, $link){
        
        $img_url = ITP_SOCIAL_BUTTONS_URL . "images/" . $this->params->get('style') . "/digg.png";
        
        return '<a href="http://digg.com/submit?url=' . $link . '&amp;title=' . $title . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Digg") . '" target="blank" >
        <img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Digg") . '" />
        </a>';
    
    }
    
    private function getFacebookButton($title, $link){
        
        $img_url = ITP_SOCIAL_BUTTONS_URL . "images/" . $this->params->get('style') . "/facebook.png";
        
        return '<a href="http://www.facebook.com/sharer.php?u=' . $link . '&amp;t=' . $title . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Facebook") . '" target="blank" >
        <img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Facebook") . '" />
        </a>';
    
    }
    
    private function getGoogleButton($title, $link){
        
        $img_url = ITP_SOCIAL_BUTTONS_URL . "images/" . $this->params->get('style') . "/google.png";
        
        return '<a href="http://www.google.com/bookmarks/mark?op=edit&amp;bkmk=' . $link . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Google Bookmarks") . '" target="blank" >
        <img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Google Bookmarks") . '" />
        </a>';
    
    }
    
    private function getStumbleuponButton($title, $link){
        
        $img_url = ITP_SOCIAL_BUTTONS_URL . "images/" . $this->params->get('style') . "/stumbleupon.png";
        
        return '<a href="http://www.stumbleupon.com/submit?url=' . $link . '&amp;title=' . $title . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Stumbleupon") . '" target="blank" >
        <img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Stumbleupon") . '" />
        </a>';
    
    }
    
    private function getTechnoratiButton($title, $link){
        
        $img_url = ITP_SOCIAL_BUTTONS_URL . "images/" . $this->params->get('style') . "/technorati.png";
        
        return '<a href="http://technorati.com/faves?add=' . $link . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Technorati") . '" target="blank" >
        <img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Technorati") . '" />
        </a>';
    
    }
    
    private function getTwitterButton($title, $link){
        
        $img_url = ITP_SOCIAL_BUTTONS_URL . "images/" . $this->params->get('style') . "/twitter.png";
        
        return '<a href="http://twitter.com/share?text=' . $title . "&amp;url=" . $link . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Twitter") . '" target="blank" >
        <img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "Twitter") . '" />
        </a>';
    
    }
    
    private function getLinkedInButton($title, $link){
        
        $img_url = ITP_SOCIAL_BUTTONS_URL . "images/" . $this->params->get('style') . "/linkedin.png";
        
        return '<a href="http://www.linkedin.com/shareArticle?mini=true&amp;url=' . $link .'&amp;title=' . $title . '" title="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "LinkedIn") . '" target="blank" >
        <img src="' . $img_url . '" alt="' . JText::sprintf("PLG_ITPSOCIALBUTTONS_SUBMIT", "LinkedIn") . '" />
        </a>';
    
    }

}
