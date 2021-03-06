<?php

require_once('db_class.php');
require_once('property_class.php');
require_once('includes.php');

class Property_dao {
	private $db;
	private $properties;
	private $types;
	private $feats;

	function __construct() {
		$this->db = new DB();
		$this->properties = array();
		$this->types = array();
		$this->feats = array();
	}

	public function get_types() {return $this->types;}
	public function query_types() {
		$type_query = "select * from property_type order by type_name";
		$type_stmt = $this->db->dbc()->prepare($type_query);

		if ($type_stmt->execute()) {
			while ($r = $type_stmt->fetch()) {
				$this->types[$r["type_id"]] = $r["type_name"];
			}
		}
	}

	public function get_feats() {return $this->feats;}
	public function query_feats() {
		$feat_query = "select * from feature order by feat_name";
		$feat_stmt = $this->db->dbc()->prepare($feat_query);

		if ($feat_stmt->execute()) {
			while ($r = $feat_stmt->fetch()) {
				$this->feats[$r["feat_id"]] = $r["feat_name"];
			}
		}
	}

	public function get_conn() {return $this->db;}
	public function set_conn($_db) {$this->db = $_db;}

	public function get_properties() {return $this->properties;}
	public function set_properties($_properties) {$this->properties = $_properties;}

	public function retrieve($criteria) {
		/**
		 * criteria = array (
		 * "street" => val
		 * "suburb" => val
		 * "state" => val
		 * "type" => val
		 * "postcode" => val
		 * "price_low" => val
		 * "price_high" => val
		 * "features" => array (
		 * 		"ft1" => val
		 * 		"ft2" => val
		 * 		"ft3"
		 * 		)
		 * )
		 */
		
		$street = $criteria["street"];
		$suburb = $criteria["suburb"];
		$state = $criteria["state"];
		$type = $criteria["type"];
		$postcode = $criteria["postcode"];
		$price_low = $criteria["price_low"];
		$price_high = $criteria["price_high"];
		$features = $criteria["features"];

		$query = "
			select * from property
			where prop_street like '%" . $street . "%'
			  and prop_suburb like '%" . $suburb . "%'
			  and prop_state like '%" . $state . "%'
			  and prop_pc like '%" . $postcode . "%'
			  and price > " . $price_low . "
			  and price < " . $price_high;

		if (count($features) > 0) {
			foreach ($features as $feat_name => $feat_id) {
				$query = $query . "
					and prop_id in (
						select prop_id from property_feature
						where feat_id = " . $feat_id . "
					)";
			}
		}

		if ($type != 0) {// 0 for all types
			$query = $query . "
				and prop_type = " . $type;
		}

		// debug the query
		//echo $query . "<br />";

		// make the execution
		// put into properties array
		$stmt = $this->db->dbc()->prepare($query);
		$stmt->execute();

		$i = 0;
		while ($r = $stmt->fetch()) {
			$this->properties[$i] = new Property($r);
			$this->properties[$i]->set_type($this->types[$r["prop_type"]]);

			// give properties their features
			// select * from property_feature where id = id
			// fetch and append them one by one to properties["features"]

			$feat_query = "select * from property_feature where prop_id = " . $r["prop_id"];
			$feat_stmt = $this->db->dbc()->prepare($feat_query);
			$feat_stmt->execute();

			while ($feat_r = $feat_stmt->fetch()) {
				$this->properties[$i]->add_feature($this->feats[$feat_r["feat_id"]]);
			}
			$i++;
		}

		//debug_array($this->properties);
	}
}

?>
