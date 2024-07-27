# Add this code in function.php or you can also create a template
# Also You can make a plugin using below code
# Also create a csv file as per your requirement
# I also include a csv file for store locater using this you can import export data

add_action("admin_notices", function() {
	echo "<div class='updated'>";
	echo "<p>";
	echo "To insert the store locations into the database, click the button to the right.";
	echo "<a class='button button-primary' style='margin:0.25em 1em' href='{$_SERVER["REQUEST_URI"]}&insert_store_locations=1'>Insert Posts</a>";
	echo "</p>";
	echo "</div>";
  });
  
add_action("admin_init", function() {
	global $wpdb, $wpsl_admin;
  
	// I'd recommend replacing this with your own code to make sure
	//  the post creation _only_ happens when you want it to.
	if ( ! isset($_GET["insert_store_locations"]) ) {
	  return;
	}

    // Path to your CSV file
    echo $csv_file = __DIR__.'/Book1.csv';


    // Open the CSV file
    if (!file_exists($csv_file) || !is_readable($csv_file)) {
        return false;
    }

    // Initialize an array to hold the CSV data
    $csv_data = array();

    // Read the CSV file
    if (($handle = fopen($csv_file, 'r')) !== false) {
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $csv_data[] = $row;
        }
        fclose($handle);
    }

    // Get the header row
    $header = array_shift($csv_data);
//     print_r($header);
// exit;
    // Make sure we disable the time limit to prevent timeouts.
    @set_time_limit(0);

    // Iterate through each row of data
    foreach ($csv_data as $row) {
        // Combine the header and row to get an associative array
        $store_data = array_combine($header, $row);

        // Make sure we set the correct post status.
        if ($store_data['active']) {
            $post_status = 'publish';
        } else {
            $post_status = 'publish';
        }

        $post = array(
            'post_type'    => 'wpsl_stores',
            'post_status'  => $post_status,
            'post_title'   => $store_data['store'],
            'post_content' => $store_data['description'],
            'post_excerpt' => $store_data['excerpt'],
        );

        $post_id = wp_insert_post($post);

        if ($post_id) {
            // Save the data from the CSV file as post meta data.
            $meta_keys = array('address', 'address2', 'city', 'state', 'zip', 'country', 'country_iso', 'lat', 'lng', 'phone', 'fax', 'url', 'email', 'hours');
            foreach ($meta_keys as $meta_key) {
                if (isset($store_data[$meta_key]) && !empty($store_data[$meta_key])) {
                    update_post_meta($post_id, 'wpsl_' . $meta_key, $store_data[$meta_key]);
                }
            }

            // If we have a thumb ID set the post thumbnail for the inserted post.
            if ($store_data['thumb_id']) {
                set_post_thumbnail($post_id, $store_data['thumb_id']);
            }
            if (!empty($store_data['featured_image_url'])) {
                wpsl_set_featured_image_from_url($post_id, $store_data['featured_image_url']);
            }
        }
    }
});

// Function to download and set the featured image from URL
function wpsl_set_featured_image_from_url($post_id, $image_url) {
    $upload_dir = wp_upload_dir();
    $image_data = file_get_contents($image_url);
    $filename = basename($image_url);
    if (wp_mkdir_p($upload_dir['path'])) {
        $file = $upload_dir['path'] . '/' . $filename;
    } else {
        $file = $upload_dir['basedir'] . '/' . $filename;
    }
    file_put_contents($file, $image_data);

    $wp_filetype = wp_check_filetype($filename, null);
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name($filename),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );
    $attach_id = wp_insert_attachment($attachment, $file, $post_id);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    wp_update_attachment_metadata($attach_id, $attach_data);
    set_post_thumbnail($post_id, $attach_id);
}
// wpsl_import_from_csv();
// Hook the function to a specific action or call it directly
//add_action('init', 'wpsl_import_from_csv');  // For example, run it when accessing the admin dashboard
