<?php
class CostAvgTable {
	public $table;
	public $cost_id;
	public $svc_id;
	public $service_label;
	public $desc;
	public $code;
	public $ro_count;
	public $parts_sale;
	public $parts_cost;
	public $labor_sale;
	
	public function __construct($label, $value) {
		$this->service_label = $label;
		$this->table = $value;
		
		// Loop through each table's item rows
		foreach ($this->table as $item) {
			$this->cost_id[]		= $item['cost_id'];
			$this->svc_id[]			= $item['svc_id'];
			$this->desc[]	  		= $item['cost_desc'];
			$this->code[]	  		= $item['cost_code'];
			$this->ro_count[]		= $item['cost_ro_count'];
			$this->parts_sale[] 	= $item['cost_parts_sale'];
			$this->parts_cost[] 	= $item['cost_parts_cost'];
			$this->labor_sale[]		= $item['cost_labor_sale'];
		}
	}
	
	public function getTotalRoCount() {
		$total_ro_count = 0;
		foreach ($this->ro_count as $ro_count) {
			$total_ro_count += $ro_count;
		}
		return $total_ro_count;
	}
	
	public function getTotalPartsSale() {
		$total_parts_sale = 0;
		$i=0;
		foreach ($this->parts_sale as $sale) {
			$total_parts_sale += $this->ro_count[$i] * $sale;
			$i++;
		}
		return $total_parts_sale;
	}
	
	public function getTotalPartsCost() {
		$total_parts_cost = 0;
		$i=0;
		foreach($this->parts_cost as $part) {
			$total_parts_cost += $this->ro_count[$i] * $part;
			$i++;
		}
		return $total_parts_cost;
	}
	
	public function getTotalLaborSale() {
		$total_labor_sale = 0;
		$i=0;
		foreach ($this->labor_sale as $labor) {
			$total_labor_sale += $this->ro_count[$i] * $labor;
			$i++;
		}
		return $total_labor_sale;
	}
	
	public function getTotalGross() {
		return ($this->getTotalPartsSale() - $this->getTotalPartsCost());
	}
	
	public function getTotalSale() {
		return ($this->getTotalPartsSale() + $this->getTotalLaborSale());
	}
	
	public function getAveragePartsSale() {
		if ($this->getTotalRoCount() !=0) {
			return number_format(($this->getTotalPartsSale() / $this->getTotalRoCount()),2);
		} else {
			return 0;
		}
	}
	
	public function getAveragePartsCost() {
		if ($this->getTotalRoCount() !=0) {
			return number_format(($this->getTotalPartsCost() / $this->getTotalRoCount()),2);
		} else {
			return 0;
		}
	}
	
	public function getAverageGross() {
		if ($this->getTotalRoCount() !=0) {
			return number_format(($this->getTotalGross() / $this->getTotalRoCount()),2);
		} else {
			return 0;
		}
	}
	
	public function getAverageLaborSale() {
		if ($this->getTotalRoCount() !=0) {
			return number_format(($this->getTotalLaborSale() / $this->getTotalRoCount()),2);
		} else {
			return 0;
		}
	}
	
	public function getAverageTotalSale() {
		if ($this->getTotalRoCount() !=0) {
			return number_format(($this->getTotalSale() / $this->getTotalRoCount()),2);
		} else {
			return 0;
		}
	}
	
	public function getAverageGrossPerSale() {
		return number_format(($this->getAverageGross() + $this->getAverageLaborSale()),2);
	}
	
	// Build the Cost Average Table
	public function buildCostAvgTable() {
		$html ='
			<div class="cost_table" id="'.$this->svc_id[0].'"><!-- this div is for AJAX updates (uses the svc_id for update target) -->
				<div class="row">
					<div class="col-sm-12">
						<div class="table-responsive">
							<form method="POST" action="assets/inc/process.inc.php" id="cost_table_form">
								<table class="table table-hover cost_avg_table">
									<thead>
										<tr>
											<th colspan="9">Service Type: <span class="red_label"> '.$this->service_label.' </span> &nbsp; <a id="tbody_'.$this->svc_id[0].'" class="add_row_link">Add Row</a></th>
										</tr>
									</thead>
									<tbody id="tbody_'.$this->svc_id[0].'" name="'.$this->service_label.'">
										<tr class="bg-lt-blue">
											<td> </td>
											<td class="text-underline no-wrap"> Description </td>
											<td class="text-underline no-wrap"> Code		</td>
											<td class="text-underline no-wrap"> RO Count	</td>
											<td class="text-underline no-wrap"> Parts Sale	</td>
											<td class="text-underline no-wrap"> Parts Cost	</td>
											<td class="text-underline no-wrap"> Parts Gross </td>
											<td class="text-underline no-wrap"> Labor Sale	</td>
											<td class="text-underline no-wrap"> Total		</td>
										</tr>';
										for ($i=0; $i<sizeof($this->table); $i++) {
											$html.=
											'<tr>
												<td>
													<span class="glyphicon glyphicon-minus-sign" id="'.$this->cost_id[$i].'" name="'.$this->cost_id[$i].'" aria-hidden="true"></span>
												</td>
												<td><input class="ops_input form-control" id="cost_desc[]" name="cost_desc[]" type="text" value="'.$this->desc[$i].'"/></td>
												<td><input class="ops_input form-control" id="cost_code[]" name="cost_code[]" type="text" value="'.$this->code[$i].'"/></td>
												<td><input class="ops_input form-control" id="cost_rocount[]" name="cost_rocount[]" type="text" value="'.$this->ro_count[$i].'"/></td>
												<td><input class="ops_input form-control" id="cost_parts_sale[]" name="cost_parts_sale[]" type="text" value="'.$this->parts_sale[$i].'"/></td>
												<td><input class="ops_input form-control" id="cost_parts_cost[]" name="cost_parts_cost[]" type="text" value="'.$this->parts_cost[$i].'"/></td>
												<td>$'.number_format(($this->parts_sale[$i] - $this->parts_cost[$i]),2).'</td>
												<td><input class="ops_input form-control" id="cost_labor_sale[]" name="cost_labor_sale[]" type="text" value="'.$this->labor_sale[$i].'"/></td>
												<td>$'.number_format(($this->parts_sale[$i] + $this->labor_sale[$i]),2).'</td>
											</tr>';
										}
									$html.=
									'</tbody>
									<tfoot>
										<tr>
											<td> </td>
											<td style="text-align: left;">Total:</td>
											<td></td>
											<td>'.$this->getTotalRoCount().'</td>
											<td>$'.number_format($this->getTotalPartsSale(),2).'</td>
											<td>$'.number_format($this->getTotalPartsCost(),2).'</td>
											<td>$'.number_format($this->getTotalGross(),2).'</td>
											<td>$'.number_format($this->getTotalLaborSale(),2).'</td>
											<td>$'.number_format($this->getTotalSale(),2).'</td>
										</tr>
										<tr>
											<td> </td>
											<td style="text-align: left;">Average:</td>
											<td>  </td>
											<td>-----</td>
											<td>$'.$this->getAveragePartsSale().'</td>
											<td>$'.$this->getAveragePartsCost().'</td>
											<td>$'.$this->getAverageGross().'</td>
											<td>$'.$this->getAverageLaborSale().'</td>
											<td>$'.$this->getAverageTotalSale().'</td>
										</tr>
										<tr class="bg-slate">
											<td>  </td>
											<td class="cost_submit_td"> <input type="submit" class="btn btn-primary form_submit btn-xs" name="cost_table_entry" value="Refresh" /> </td>
											<td>  </td>
											<td>  </td>
											<td>  </td>
											<td>  </td>
											<td colspan="2">Avg Gross Per Sale: </td>
											<td class="bg-success">$'.$this->getAverageGrossPerSale().'</td>
										</tr>
									</tfoot>
								</table>
								<input type="hidden" name="cost_table_svc_name" value="'.$this->service_label.'" />
								<input type="hidden" name="cost_table_svc_id" value="'.$this->svc_id[0].'" />
								<input type="hidden" name="token" value="'.$_SESSION['token'].'" />
								<input type="hidden" name="action" value="cost_table_entry" />
							</form>
						</div>
					</div>
				</div>
			</div><!-- end div svc_id(will be a number corresponding to svc_id -->';
		return $html;
	}
	
	public function exportCostAvgTable() {
		$output = "";
		$output .= "Service Type: ".$this->service_label;
		$output .= "\n";
		$output .= "Description,";
		$output .= "Code,";
		$output .= "RO Count,";
		$output .= "Parts Sale,";
		$output .= "Parts Cost,";
		$output .= "Parts Gross,";
		$output .= "Labor Sale,";
		$output .= "Total";
		$output .= "\n";
		for ($i=0; $i<sizeof($this->table); $i++) {
			$output .= $this->desc[$i].",";
			$output .= $this->code[$i].",";
			$output .= $this->ro_count[$i].",";
			$output .= $this->parts_sale[$i].",";
			$output .= $this->parts_cost[$i].",";
			$output .=($this->parts_sale[$i] - $this->parts_cost[$i]).",";
			$output .= $this->labor_sale[$i].",";
			$output .=($this->parts_sale[$i] + $this->labor_sale[$i]).",";
			$output .= "\n";
		}
		$output .= "Total: ,";
		$output .= ",";
		$output .= $this->getTotalRoCount().",";
		$output .= $this->getTotalPartsSale().",";
		$output .= $this->getTotalPartsCost().",";
		$output .= $this->getTotalGross().",";
		$output .= $this->getTotalLaborSale().",";
		$output .= $this->getTotalSale();
		$output .= "\n";
		$output .= "Average: ,";
		$output .= ",";
		$output .= "----,";
		$output .= $this->getAveragePartsSale().",";
		$output .= $this->getAveragePartsCost().",";
		$output .= $this->getAverageGross().",";
		$output .= $this->getAverageLaborSale().",";
		$output .= $this->getAverageTotalSale();
		$output .= "\n";
		$output .= "Average Gross Per Sale: ,";
		$output .= $this->getAverageGrossPerSale();
		$output .= "\n";
		$output .= "\n";
		$output .= "\n";
		
		return $output;
	}
}
?>