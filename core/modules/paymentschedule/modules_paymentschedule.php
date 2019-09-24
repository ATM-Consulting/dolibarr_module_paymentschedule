<?php
/**
 * \file paymentschedule/modules/paymentschedule/modules_agefodd.php
 * \ingroup project
 * \brief File that contain parent class for projects models
 * and parent class for projects numbering models
 */
require_once (DOL_DOCUMENT_ROOT . "/core/class/commondocgenerator.class.php");

abstract class ModelePDFPaymentschedule extends CommonDocGenerator {
	var $error = '';

	/**
	 * Return list of active generation modules
	 *
	 * @param DoliDB $db handler
	 * @param string $maxfilenamelength length of value to show
	 * @return array of templates
	 */
	static function liste_modeles($db, $maxfilenamelength = 0) {
		global $langs;

		$type = 'paymentschedule';
		$liste = array ();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$liste=getListOfModels($db,$type,$maxfilenamelength);

		return $liste;
	}

	/**
	 *
	 * @param string $txt
	 */
	public function getRealHeightLine($type = '') {
		global $conf;

		// Determine if jump pages is needed
		$this->pdf->startTransaction();

		$this->pdf->setPrintHeader(false);
		$this->pdf->setPrintFooter(false);
		$this->pdf->AddPage();

		// store starting values
		$start_y = $this->pdf->GetY();
		// print '$start_y='.$start_y.'<br>';

		$start_page = $this->pdf->getPage();

		$height = 0;

		// print content
		if ($type == 'head') {
			$this->_pagehead($this->pdf->ref_object, 1, $this->outputlangs);
		} elseif ($type == 'foot') {
			$height = $this->_pagefoot($this->pdf->ref_object, $this->outputlangs);
		}

		if (empty($height)) {
			// get the new Y
			$end_y = $this->pdf->GetY();
			$end_page = $this->pdf->getPage() - 1;
			// calculate height
			// print '$end_y='.$end_y.'<br>';
			// print '$end_page='.$end_page.'<br>';

			if (($end_page == $start_page || $end_page == 0) && $end_y > $start_y) {
				$height = $end_y - $start_y;
				// print 'aa$height='.$height.'<br>';
			} else {
				for($page = $start_page; $page <= $end_page; $page ++) {
					$this->pdf->setPage($page);
					// print '$page='.$page.'<br>';
					if ($page == $start_page) {
						// first page
						$height = $this->page_hauteur - $start_y - $this->marge_basse;
						// print '$height=$this->page_hauteur - $start_y - $this->marge_basse='.$this->page_hauteur .'-'. $start_y .'-'. $this->marge_basse.'='.$height.'<br>';
					} elseif ($page == $end_page) {
						// last page
						// print '$height='.$height.'<br>';
						$height += $end_y - $this->marge_haute;
						// print '$height += $end_y - $this->marge_haute='.$end_y.'-'. $this->marge_haute.'='.$height.'<br>';
					} else {
						// print '$height='.$height.'<br>';
						$height += $this->page_hauteur - $this->marge_haute - $this->marge_basse;
						// print '$height += $this->page_hauteur - $this->marge_haute - $this->marge_basse='.$this->page_hauteur .'-'. $this->marge_haute .'-'. $this->marge_basse.'='.$height.'<br>';
					}
				}
			}
		}
		$this->pdf->setPrintHeader(true);
		$this->pdf->setPrintFooter(true);

		// restore previous object
		$this->pdf = $this->pdf->rollbackTransaction();
		// print '$heightfinnal='.$height.'<br>';

		// exit;
		return $height;
	}
}
