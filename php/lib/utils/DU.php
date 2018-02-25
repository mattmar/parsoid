<?php

namespace Parsoid\Lib\Utils;

require_once "../../../config/WikitextConstants.php";
use Parsoid\Lib\Config\WikitextConstants;

class DU {
	const TPL_META_TYPE_REGEXP = '/(?:^|\s)(mw:(?:Transclusion|Param)(?:\/End)?)(?=$|\s)/';

	// For an explanation of what TSR is, see dom.computeDSR.js
	//
	// TSR info on all these tags are only valid for the opening tag.
	// (closing tags dont have attrs since tree-builder strips them
	//  and adds meta-tags tracking the corresponding TSR)
	//
	// On other tags, a, hr, br, meta-marker tags, the tsr spans
	// the entire DOM, not just the tag.
	//
	// This code is not in mediawiki.wikitext.constants.js because this
	// information is Parsoid-implementation-specific.
	private static $WtTagsWithLimitedTSR;

	public static function init() {
		self::$WtTagsWithLimitedTSR = array(
			"b"  =>      true,
			"i"  =>      true,
			"h1" =>      true,
			"h2" =>      true,
			"h3" =>      true,
			"h4" =>      true,
			"h5" =>      true,
			"ul" =>      true,
			"ol" =>      true,
			"dl" =>      true,
			"li" =>      true,
			"dt" =>      true,
			"dd" =>      true,
			"table" =>   true,
			"caption" => true,
			"tr" =>      true,
			"td" =>      true,
			"th" =>      true,
			"hr" =>      true, // void element
			"br" =>      true, // void element
			"pre" =>     true,
		);
	}

	public static function isElt( $node ) {
		return $node && $node->nodeType === 1;
	}

	public static function isText( $node ) {
		return $node && $node->nodeType === 3;
	}

	public static function isBody( $node ) {
		return $node && $node->nodeName === 'body';
	}

	/**
	 * Determine whether the node matches the given nodeName and typeof
	 * attribute value.
	 *
	 * @param {Node} n
	 * @param {string} name Passed into #hasNodeName
	 * @param {string} type Expected value of "typeof" attribute
	 */
	public static function isNodeOfType($n, $name, $type) {
		return $n->nodeName === $name && $n->getAttribute("typeof") === $type;
	}

	/**
	 * Check whether a meta's typeof indicates that it is a template expansion.
	 *
	 * @param {string} nType
	 */
	public static function isTplMetaType($nType) {
		return preg_match(self::TPL_META_TYPE_REGEXP, $nType);
	}

	public static function getDataParsoid( $node ) {
		// fixme: inefficient!!
		// php dom impl doesn't provide the DOMUserData field => cannot cache this right now
		return json_decode($node->getAttribute('data-parsoid'), true);
	}

	public static function setDataParsoid( $node, $dp ) {
		$node->setAttribute( 'data-parsoid', json_encode( $dp ) );
	}

	/**
	 * Check whether a node's data-parsoid object includes
	 * an indicator that the original wikitext was a literal
	 * HTML element (like table or p)
	 *
	 * @param {Object} dp
	 *   @param {string|undefined} [dp.stx]
	 */
	public static function hasLiteralHTMLMarker( $dp ) {
		return isset($dp['stx']) && $dp['stx'] === 'html';
	}

	/**
	 * Run a node through #hasLiteralHTMLMarker
	 */
	public static function isLiteralHTMLNode($node) {
		return ($node &&
			self::isElt($node) &&
			self::hasLiteralHTMLMarker(self::getDataParsoid($node)));
	}

	/**
	 * Check whether a node is a meta signifying the start of a template expansion.
	 */
	public static function isTplStartMarkerMeta($node) {
		if ($node->nodeName == "meta") {
			$t = $node->getAttribute("typeof");
			return self::isTplMetaType($t) && !preg_match('/\/End(?=$|\s)/', $t);
		} else {
			return false;
		}
	}

	/**
	 * Check whether a pre is caused by indentation in the original wikitext.
	 */
	public static function isIndentPre($node) {
		return $node->nodeName === "pre" && !self::isLiteralHTMLNode($node);
	}

	public static function isFormattingElt( $node ) {
		return $node && isset( WikitextConstants::$HTML['FormattingTags'][ $node->nodeName ] );
	}

	public static function isList( $n ) {
		return $n && isset( WikitextConstants::$ListTags[$n->nodeName] );
	}

	public static function isQuoteElt( $n ) {
		return $n && isset( WikitextConstants::$WTQuoteTags[$n->nodeName] );
	}

	public static function tsrSpansTagDOM($n, $parsoidData) {
		// - tags known to have tag-specific tsr
		// - html tags with 'stx' set
		// - span tags with 'mw:Nowiki' type
		$name = $n->nodeName;
		return !(
			isset(self::$WtTagsWithLimitedTSR[$name]) ||
			self::hasLiteralHTMLMarker($parsoidData) ||
			self::isNodeOfType($n, 'span', 'mw:Nowiki')
		);
	}

	public static function deleteNode( $node ) {
		$node->parentNode->removeChild( $node );
	}

	public static function migrateChildren( $from, $to, $beforeNode = null) {
		while ( $from->firstChild ) {
			$to->insertBefore( $from->firstChild, $beforeNode );
		}
	}

	public static function isGeneratedFigure( $n ) {
		return self::isElt( $n ) && preg_match( '/(^|\s)mw:(?:Image|Video|Audio)(\s|$|\/)/', $n->getAttribute("typeof") );
	}

	/**
	 * Check whether a typeof indicates that it signifies an
	 * expanded attribute.
	 */
	public static function hasExpandedAttrsType( $node ) {
		$nType = $node->getAttribute('typeof');
		return preg_match( '/(?:^|\s)mw:ExpandedAttrs(\/[^\s]+)*(?=$|\s)/', $nType );
	}

	/**
	 * Helper functions to detect when an A-node uses [[..]]/[..]/... style
	 * syntax (for wikilinks, ext links, url links). rel-type is not sufficient
	 * anymore since mw:ExtLink is used for all the three link syntaxes.
	 */
	public static function usesWikiLinkSyntax( $aNode, $dp ) {
		if ($dp === null) {
			$dp = self::getDataParsoid($aNode);
		}

		// SSS FIXME: This requires to be made more robust
		// for when dp['stx'] value is not present
		return $aNode->getAttribute("rel") === "mw:WikiLink" ||
			(isset($dp['stx']) && $dp['stx'] !== "url" && $dp['stx'] !== "magiclink");
	}

	public static function usesExtLinkSyntax( $aNode, $dp ) {
		if ($dp === null) {
			$dp = self::getDataParsoid($aNode);
		}

		// SSS FIXME: This requires to be made more robust
		// for when $dp['stx'] value is not present
		return $aNode->getAttribute("rel") === "mw:ExtLink" &&
			(!isset($dp['stx']) || ($dp['stx'] !== "url" && $dp['stx'] !== "magiclink"));
	}

	public static function usesURLLinkSyntax($aNode, $dp) {
		if ($dp === null) {
			$dp = self::getDataParsoid($aNode);
		}

		// SSS FIXME: This requires to be made more robust
		// for when $dp['stx'] value is not present
		return $aNode->getAttribute("rel") === "mw:ExtLink" &&
			isset($dp['stx']) && ($dp['stx'] === "url" || $dp['stx'] === "magiclink");
	}

	/**
	 * Find how much offset is necessary for the DSR of an
	 * indent-originated pre tag.
	 *
	 * @param {TextNode} textNode
	 * @return {number}
	 */
	public static function indentPreDSRCorrection($textNode) {
		// NOTE: This assumes a text-node and doesn't check that it is one.
		//
		// FIXME: Doesn't handle text nodes that are not direct children of the pre
		if (self::isIndentPre($textNode->parentNode)) {
			if ($textNode->parentNode->lastChild === $textNode) {
				// We dont want the trailing newline of the last child of the pre
				// to contribute a pre-correction since it doesn't add new content
				// in the pre-node after the text
				$numNLs = preg_match_all('/\n./', $textNode->nodeValue);
			} else {
				$numNLs = preg_match_all('/\n/', $textNode->nodeValue);
			}
			return $numNLs;
		} else {
			return 0;
		}
	}

/*
	// Map an HTML DOM-escaped comment to a wikitext-escaped comment.
	public static function decodeComment($comment) {
		// Undo HTML entity escaping to obtain "true value" of comment.
		$trueValue = Util.decodeEntities($comment);
		// ok, now encode this "true value" of the comment in such a way
		// that the string "-->" never shows up.  (See above.)
		return $trueValue
			.replace(/--(&(amp;)*gt;|>)/g, function(s) {
				return s === '-->' ? '--&gt;' : '--&amp;' + s.slice(3);
			});
	}
*/

	// Utility function: we often need to know the wikitext DSR length for
	// an HTML DOM comment value.
	public static function decodedCommentLength($node) {
		# assert(self::isComment($node));
		// Add 7 for the "<!--" and "-->" delimiters in wikitext.
		#return mb_strlen(self::decodeComment($node->data)) + 7;
		return mb_strlen($node->textContent) + 7;
	}

	private static function hasRightType($n) {
		return preg_match('/(?:^|\s)mw:DOMFragment(?=$|\s)/', $n->getAttribute("typeof"));
	}

	private static function previousSiblingIsWrapper($sibling, $abt) {
		return $sibling &&
			self::isElt($sibling) &&
			$abt === $sibling->getAttribute("about") &&
			self::hasRightType($sibling);
	}

	public static function isDOMFragmentWrapper($node) {
		if (!self::isElt($node)) {
			return false;
		}

		$about = $node->getAttribute("about");
		return $about && (
			self::hasRightType($node) ||
			self::previousSiblingIsWrapper($node->previousSibling, $about)
		);
	}

	// FIXME: Should be in Utils.php
	public static function isValidDSR( $dsr ) {
		return $dsr &&
			is_numeric( $dsr[0] ) && $dsr[0] >= 0 &&
			is_numeric( $dsr[1] ) && $dsr[1] >= 0;
	}

	public static function dumpDOM($node, $str) {
		/* nothing */
	}
}