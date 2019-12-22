<?php
/*
	Produced 2019
	By https://github.com/amattu2
	Copy Alec M.
	License GNU Affero General Public License v3.0
*/

// Template Interface
interface LabelTemplate {
	// Add Single Label
	function add($string, $row = 0, $col = 0);

	// Build Template
	function build();
}

// Exception Classes
class BadValueException extends Exception {}
class InvalidStateException extends Exception {}

// Avery 5160 Label Class
class Avery_5160 extends FPDF implements LabelTemplate {
	// Class Variables
	protected $open_state = 1;
	protected $items = Array();
	protected $top = 13;
	protected $left = 5;
	protected $config_col_width = 67;
	protected $config_col_sep_width = 3;
	protected $config_max_linebreak = 4;
	protected $config_row_count = 10;
	protected $config_col_count = 3;

	// Add Label
	// $string => Multiple line address string
	// $row => Zero-indexed row number
	// $col => Zero-indexed column number
	function add($string, $row = -1, $col = -1) {
		// Checks
		if (empty($string) || substr_count($string, "\n") > $this->config_max_linebreak) {
			throw new BadValueException("Label string provided is empty or contains too many lines");
		}
		if ($row < -1 || $col < -1 || ($row + 1) > $this->config_row_count || ($col + 1) > $this->config_col_count) {
			throw new BadValueException("Row or column value specified is invalid");
		}

		// Append
		$this->items[] = Array(
			"S" => trim($string),
			"R" => $row,
			"C" => $col
		);
	}

	// Build PDF
	function build() {
		// Checks
		if ($this->open_state != 1) {
			throw new InvalidStateException("Attempt to build onto an existing PDF");
		}

		// Variables
		$bottom = $this->GetPageHeight() - $this->top;
		$right = $this->GetPageWidth() - $this->left;
		$config_row_height = (($bottom - $this->top) / $this->config_row_count);
		$config_items_per_page = $this->config_row_count * $this->config_col_count;
		//$limit = ceil(count($this->items) / $config_items_per_page);
		$current_row = 0;
		$current_col = 0;
		$current_page = 0;
		$current_item_count = 0;

		// Loop
		foreach ($this->items as $item) {
			// Checks
			if ($current_item_count++ > $config_items_per_page) {
				$this->AddPage("P", "Letter");
				$current_item_count = 1;
				$current_page++;
				$current_col = 0;
				$current_row = 0;
			}
			if ($current_row >= $this->config_row_count) {
				$current_col++;
				$current_row = 0;
			}
			if ($current_col >= $this->config_col_count) {
				$this->AddPage("P", "Letter");
				$current_item_count = 1;
				$current_page++;
				$current_col = 0;
				$current_row = 0;
			}
			if ($item["R"] > $this->config_row_count || $item["R"] < 0) {
				$item["R"] = $current_row++;
			}
			if ($item["C"] > $this->config_col_count || $item["C"] < 0) {
				$item["C"] = $current_col;
			}

			// Move Position
			$this->setY(($item["R"] > 0 ? $this->top + ($config_row_height * $item["R"]) + 2 : $this->top + 2));
			$this->setX(($item["C"] > 0 ? $this->left + ($this->config_col_width * $item["C"]) + ($this->config_col_sep_width * $item["C"]) : $this->left));

			// Output
			$this->MultiCell($this->config_col_width, ($config_row_height / 3.5), $item["S"], false, "C");
		}

		// Close PDF
		$this->open_state = 0;
	}
}
?>
