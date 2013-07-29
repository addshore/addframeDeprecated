<?php

namespace Addframe;

/**
 * This class is designed to represent a Site Page
 * @author Addshore
 **/
class Page {

	/** @var Site siteUrl for associated site */
	public $site;
	/** @var string title of Page including namespace */
	public $title;

	/** @var string text of Page */
	protected $text;
	/** @var string pageid for Page */
	protected $pageid;
	/** @var string namespace id number eg. 2 */
	protected $nsid;
	/** @var array of categories the page is in */
	protected $categories;
	/** @var Entity entity that is associated with the page */
	protected $entity;
	/** @var parser entity that is associated with the page */
	protected $parser;

	/**
	 * @param $site
	 * @param $title
	 */
	public function __construct( $site, $title ) {
		$this->site = $site;
		$this->title =  $title;
	}

	/**
	 * @return string
	 */
	public function getNsid() {
		if( $this->nsid == null ){
			$this->nsid = $this->site->getNamespaceIdFromTitle( $this->title );
		}
		return $this->nsid;
	}

	/**
	 * @return string
	 */
	public function getPageid() {
		return $this->pageid;
	}

	/**
	 * @return Site
	 */
	public function getSite() {
		return $this->site;
	}

	/**
	 * @param bool $force force getting new text?
	 * @return string
	 */
	public function getText( $force = false) {
		if( $this->text == null || $force == true ){
			$this->text = $this->getSite()->getPageTextFromPageTitle( $this->title );
		}
		return $this->text;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @return string The title with the namespace removed if possible
	 */
	public function getTitleWithoutNamespace() {
		$this->getNsid();

		if ( $this->nsid != null && $this->nsid != '0' ) {
			$explode = explode( ':', $this->title, '2' );
			return $explode[1];
		}
		return $this->title;
	}

	public function isFullyEditProtected(){
		$q['action'] = 'query';
		$q['prop'] = 'info';
		$q['titles'] = $this->title;
		$q['inprop'] = 'protected';
		$result = $this->site->doRequest( $q );
		foreach( $result['query']['pages'] as $page ){
			if( isset( $page['protection'] ) ){
				foreach( $page['protection'] as $protection ){
					if( $protection['type'] == 'edit' && $protection['level'] == 'sysop'){
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Parsers the current text. Sets and returns the parser object.
	 *
	 * @return parser
	 */
	public function parse() {
		$parser = new parser( $this->title, $this->getText() );
		$parser->parse();
		$this->parser = $parser;
		return $this->parser;
	}

	/**
	 * @return string Normalise the namespace of the title if possible.
	 */
	public function normaliseTitleNamespace() {
		$this->getNsid();

		if ( $this->nsid != '0' ) {
			$siteNamespaces = $this->site->requestNamespaces();
			$normalisedNamespace = $siteNamespaces[$this->nsid][0];

			$explosion = explode( ':', $this->title, 2 );
			$explosion[0] = $normalisedNamespace;
			$this->title = implode( ':', $explosion );
		}
		return $this->title;

	}

	/**
	 * @return null|Entity The entity that this page is included on
	 */
	public function getEntity() {
		$q['action'] = 'query';
		$q['prop'] = 'pageprops';
		$q['titles'] = $this->title;
		$result = $this->site->doRequest( $q );
		foreach ( $result['query']['pages'] as $page ) {
			if ( isset( $page['pageprops']['wikibase_item'] ) ) {
				$this->entity = new Entity( $this->site->getWikibase(), $page['pageprops']['wikibase_item'] );
				return $this->entity;
			}
		}
		return null;
	}

	/**
	 * @return array of interwikilinks [1] => array(site=>en,link=>Pagename) etc.
	 */
	//@todo add data about site type here i.e. wiki or wikivoyage?
	public function getInterwikisFromtext() {
		$text = $this->getText();

		$toReturn = array();
		//@todo this list of langs should definatly come from a better place...
		preg_match_all( '/\n\[\[' . Globals::$regex['langs'] . ':([^\]]+)\]\]/', $text, $matches );
		foreach ( $matches[0] as $key => $match ) {
			$toReturn[] = Array( 'site' => $matches[1][$key], 'link' => $matches[2][$key] );
		}
		return $toReturn;
	}

	/**
	 * Finds interwikis on the page and returns an array of pages for them
	 *
	 * @return array
	 */
	public function getPagesFromInterwikiLinks() {
		$pages = array();

		$interwikis = $this->getInterwikisFromtext();
		foreach ( $interwikis as $interwikiData ) {
			$site = $this->site->family->getSiteFromSiteid( $interwikiData['site'] . $this->site->getType() );
			if ( $site instanceof Site ) {
				$pages[] = $site->newPageFromTitle( $interwikiData['link'] );
			}
		}

		return $pages;
	}

	/**
	 * @return array of Pages linked to using inter project links
	 */
	public function getPagesFromInterprojectLinks() {
		$text = $this->getText();
		$pages = array();

		preg_match_all( '/\[\[' . Globals::$regex['sites'] . ':(' . Globals::$regex['langs'] . ':)?([^\]]+?)\]\]/i', $text, $matches );
		foreach ( $matches[0] as $key => $match ) {
			$parts = array();

			//set the site
			if ( stristr( $matches[1][$key], 'wikipedia' ) ) {
				$parts['site'] = 'wiki';
			} else {
				$parts['site'] = strtolower( $matches[1][$key] );
			}
			//set the language
			if ( $matches[3][$key] == '' ) {
				$parts['lang'] = $this->site->getLanguage();
			} else {
				$parts['lang'] = $matches[3][$key];
			}
			$parts['title'] = $matches[4][$key];

			$site = $this->site->family->getSiteFromSiteid( $parts['lang'] . $parts['site'] );
			if ( $site instanceof Site ) {
				$pages[] = $site->newPageFromTitle( $parts['title'] );
			}
		}

		return $pages;
	}

	/**
	 * @return array of Pages linked to using inter project / page templates
	 */
	public function getPagesFromInterprojectTemplates() {
		$text = $this->getText();
		$pages = array();

		preg_match_all( '/\{\{(wikipedia|wikivoyage)(\|([^\]]+?))\}\}/i', $text, $matches );
		foreach ( $matches[0] as $key => $match ) {
			$parts = array();
			//set the site
			if ( stristr( $matches[1][$key], 'wikipedia' ) ) {
				$parts['site'] = 'wiki';
			} else {
				$parts['site'] = strtolower( $matches[1][$key] );
			}
			$parts['lang'] = $this->site->getLanguage();
			$parts['title'] = $matches[3][$key];

			$site = $this->site->family->getSiteFromSiteid( $parts['lang'] . $parts['site'] );
			if ( $site instanceof Site ) {
				$pages[] = $site->newPageFromTitle( $parts['title'] );
			}

		}

		return $pages;
	}

	/**
	 * @param null $hidden
	 * @return mixed
	 * @todo return an array of category objects which would extend Page
	 * @todo refactor into site->getCategoriesFromPageTitle
	 */
	public function getCategories( $hidden = null ) {
		$param['titles'] = $this->title;
		if ( $hidden === true ) {
			$param['clshow'] = 'hidden';
		} elseif ( $hidden === false ) {
			$param['clshow'] = '!hidden';
		}

		$result = $this->site->requestPropCategories( $param );

		foreach ( $result->value['query']['pages'] as $x ) {
			$this->pageid = $x['pageid'];
			$this->nsid = $x['nsid'];
			$this->categories = $x['categories'];
		}
		return $this->categories;
	}

	/**
	 * @param null $summary string to save the Page with
	 * @param bool $minor should be minor?
	 * @return string
	 */
	public function save( $summary = null, $minor = false ) {
		echo "Saved page " . $this->title . "\n";
		return $this->site->requestEdit( $this->title, $this->getText(), $summary, $minor );
	}

	/**
	 * @param $text string to append to $text
	 */
	public function appendText( $text ) {
		$this->text = $this->getText() . $text;
	}

	/**
	 * @param $text string to prepend to $text
	 */
	public function prependText( $text ) {
		$this->text = $text . $this->getText();
	}

	/**
	 * Empties the text of the page
	 */
	public function emptyText() {
		$this->text = "";
	}

	/**
	 * Find a string
	 * @param $string string The string that you want to find.
	 * @return bool value (1 found and 0 not-found)
	 **/
	public function findString( $string ) {
		if ( strstr( $this->getText(), $string ) )
			return 1; else
			return 0;
	}

	/**
	 * Replace a string
	 * @param $string string The string that you want to replace.
	 * @param $newstring string The string that will replace the present string.
	 */
	public function replaceString( $string, $newstring ) {
		$this->text = str_replace( $string, $newstring, $this->getText() );
	}

	public function pregReplace( $patern, $replacment ) {
		$this->text = preg_replace( $patern, $replacment, $this->getText() );
	}

	public function removeRegexMatched( $patern ) {
		$this->pregReplace( $patern, '' );
	}

	/**
	 * Gets the entity for the article and removes all possible interwiki links
	 * from the page text.
	 */
	public function removeEntityLinksFromText() {
		$text = $this->getText();
		$baseEntity = $this->getEntity();
		$counter = 0;

		if ( ! $baseEntity instanceof Entity ) {
				return false;
		}
		$baseEntity->load();

		foreach ( $baseEntity->languageData['sitelinks'] as $sitelink ) {
			$site = $this->site->family->getSiteFromSiteid( $sitelink['site'] );
			if( $site instanceof Site && $this->site->getType() == $site->getType() ){
				$iwPrefix = $site->getIwPrefix();
				$page = $site->newPageFromTitle( $sitelink['title'] );
				$titleEnd = $page->getTitleWithoutNamespace();
				$possibleNamespaces = $site->requestNamespaces();
				$possibleNamespaces = $possibleNamespaces[$page->getNsid()];

				//@todo this could all be improved with something like getRegexForTitle or  getRegexForInterwikiLink
				foreach ( $possibleNamespaces as $namespace ) {
					if ( $namespace != "" ) {
						$titleVarient = $namespace . ':' . $titleEnd;
					} else {
						$titleVarient = $titleEnd;
					}
					//@todo remember (zh-min-nan|nan) and (nb|no) (they are the same site)
					$lengthBefore = strlen( $text );
					$removeLink = '/\n ?\[\[' . $iwPrefix . ' ?: ?' . str_replace( ' ', '( |_)', preg_quote( $titleVarient, '/' ) ) . ' ?\]\] ?/';
					$this->removeRegexMatched( $removeLink );
					if ( $lengthBefore < strlen( $text ) ) {
						$counter = $counter + 1;
						echo "Removed link! $iwPrefix:$titleVarient\n";
					}
				}
			}

		}

		if( count( $this->getInterwikisFromtext() ) == 0 ){

			if( $this->getNsid() == 10 ){
				//Remove empty no include tags
				$this->removeRegexMatched('/<noinclude>\s+?<\/noinclude>/');
			}

		$this->removeRegexMatched('/<!-- ?(interwikis?( links?)?|other (wiki|language)s?) ?-->/i');

		//Remove extra space we might have left at the end
		$this->pregReplace( '/(\n\n)\n+$/', "$1" );
		$this->pregReplace( '/^(\n|\r){0,5}$/', "" );

		}

		if( $counter != 0 ){
			return $counter;
		}
		return false;
	}
}