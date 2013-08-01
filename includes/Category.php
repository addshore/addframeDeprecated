<?php

namespace Addframe;

/**
 * Represents a wiki Category
 * @since 0.0.3
 * @author Addshore
 **/

class Category extends Page{

	public function getCategoryMembers( $limit = 5000 ){
		echo "Getting members of ".$this->title."\n";
		$returnArray  = array();
		$params['cmtitle'] = $this->title;
		$params['cmlimit'] = $limit;
//		$params['cmtype'] = 'page|subcat';
		$result = $this->site->requestListCategoryMembers( $params );
		$returnArray = array_merge( $returnArray, $result['query']['categorymembers'] );
		foreach( $result['query']['categorymembers'] as $member){
			if($member['ns'] == '14' ){
				$innerCat = $this->site->newCategoryFromTitle( $member['title'] );
				if( $innerCat instanceof Category ){
					$returnArray = array_merge( $returnArray, $innerCat->getCategoryMembers() );
				}

			}
		}
		return $returnArray;
	}

}