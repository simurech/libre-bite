<?php
/**
 * Klasse: WC-Bestellliste – Standort-Spalte und Filter
 *
 * Fügt der WooCommerce-Bestellliste eine Standort-Spalte hinzu und ermöglicht
 * das Filtern nach Standort. Unterstützt HPOS (High-Performance Order Storage)
 * und das klassische Post-basierte Format.
 *
 * @package LibreBite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LBite_Order_List {

	private $loader;

	public function __construct( $loader ) {
		$this->loader = $loader;

		// HPOS-Bestellliste (/wp-admin/admin.php?page=wc-orders)
		$this->loader->add_filter( 'manage_woocommerce_page_wc-orders_columns', $this, 'add_location_column' );
		$this->loader->add_action( 'manage_woocommerce_page_wc-orders_custom_column', $this, 'render_location_column_hpos', 10, 2 );
		$this->loader->add_action( 'woocommerce_order_list_table_restrict_manage_orders', $this, 'render_location_filter' );
		$this->loader->add_filter( 'woocommerce_order_query_args', $this, 'apply_location_filter_hpos' );

		// Klassische Post-basierte Bestellliste (edit.php?post_type=shop_order)
		$this->loader->add_filter( 'manage_shop_order_posts_columns', $this, 'add_location_column' );
		$this->loader->add_action( 'manage_shop_order_posts_custom_column', $this, 'render_location_column_legacy', 10, 2 );
		$this->loader->add_action( 'restrict_manage_posts', $this, 'render_location_filter_legacy' );
		$this->loader->add_action( 'pre_get_posts', $this, 'apply_location_filter_legacy' );
	}

	/**
	 * Standort-Spalte nach der Datum-Spalte einfügen.
	 */
	public function add_location_column( array $columns ): array {
		$reordered = array();
		foreach ( $columns as $key => $label ) {
			$reordered[ $key ] = $label;
			if ( 'order_date' === $key ) {
				$reordered['lbite_location'] = __( 'Location', 'libre-bite' );
			}
		}
		return $reordered;
	}

	/**
	 * Standort-Spalte rendern (HPOS).
	 *
	 * @param string   $column Spalten-Schlüssel.
	 * @param WC_Order $order  Bestellobjekt.
	 */
	public function render_location_column_hpos( string $column, WC_Order $order ): void {
		if ( 'lbite_location' !== $column ) {
			return;
		}
		$location_id = (int) $order->get_meta( '_lbite_location_id' );
		echo $location_id ? esc_html( get_the_title( $location_id ) ) : '—';
	}

	/**
	 * Standort-Spalte rendern (Legacy).
	 *
	 * @param string $column  Spalten-Schlüssel.
	 * @param int    $post_id Post-ID der Bestellung.
	 */
	public function render_location_column_legacy( string $column, int $post_id ): void {
		if ( 'lbite_location' !== $column ) {
			return;
		}
		$order = wc_get_order( $post_id );
		if ( ! $order ) {
			echo '—';
			return;
		}
		$location_id = (int) $order->get_meta( '_lbite_location_id' );
		echo $location_id ? esc_html( get_the_title( $location_id ) ) : '—';
	}

	/**
	 * Filter-Dropdown ausgeben (HPOS).
	 */
	public function render_location_filter(): void {
		$this->output_location_filter_select();
	}

	/**
	 * Filter-Dropdown ausgeben (Legacy – nur für shop_order Post-Type).
	 */
	public function render_location_filter_legacy(): void {
		global $typenow;
		if ( 'shop_order' !== $typenow ) {
			return;
		}
		$this->output_location_filter_select();
	}

	/**
	 * Standort-Filter-Dropdown HTML ausgeben.
	 */
	private function output_location_filter_select(): void {
		$locations = get_posts( array(
			'post_type'      => 'lbite_location',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		if ( empty( $locations ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected = isset( $_GET['lbite_location_filter'] ) ? intval( wp_unslash( $_GET['lbite_location_filter'] ) ) : 0;
		?>
		<select name="lbite_location_filter">
			<option value=""><?php esc_html_e( 'All Locations', 'libre-bite' ); ?></option>
			<?php foreach ( $locations as $loc ) : ?>
				<option value="<?php echo esc_attr( $loc->ID ); ?>" <?php selected( $selected, $loc->ID ); ?>>
					<?php echo esc_html( $loc->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * HPOS-Abfrage nach Standort filtern.
	 *
	 * @param array $query_args wc_get_orders()-Argumente.
	 */
	public function apply_location_filter_hpos( array $query_args ): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$location_id = isset( $_GET['lbite_location_filter'] ) ? intval( wp_unslash( $_GET['lbite_location_filter'] ) ) : 0;
		if ( $location_id > 0 ) {
			$query_args['meta_query'][] = array(
				'key'   => '_lbite_location_id',
				'value' => $location_id,
				'type'  => 'NUMERIC',
			);
		}
		return $query_args;
	}

	/**
	 * Legacy-Abfrage (WP_Query) nach Standort filtern.
	 *
	 * @param WP_Query $query Aktuelle Hauptabfrage.
	 */
	public function apply_location_filter_legacy( WP_Query $query ): void {
		global $pagenow, $typenow;

		if ( 'edit.php' !== $pagenow || 'shop_order' !== $typenow || ! $query->is_main_query() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$location_id = isset( $_GET['lbite_location_filter'] ) ? intval( wp_unslash( $_GET['lbite_location_filter'] ) ) : 0;
		if ( $location_id > 0 ) {
			$meta_query   = $query->get( 'meta_query' ) ?: array();
			$meta_query[] = array(
				'key'   => '_lbite_location_id',
				'value' => $location_id,
				'type'  => 'NUMERIC',
			);
			$query->set( 'meta_query', $meta_query );
		}
	}
}
