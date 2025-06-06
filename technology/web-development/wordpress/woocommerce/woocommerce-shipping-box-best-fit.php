<?php

// WooCommerce - Selects the best-fitting shipping box using BoxPacker (https://github.com/dvdoug/BoxPacker) for a WooCommerce order based on item dimensions and weight, and displays this information in the order details
// Last update: 2025-05-05


// Add best package fit inside WooCommerce orders using a custom field - run action once (run on WP Console)
// $orders = wc_get_orders(['limit' => -1]);
// foreach ($orders as $order) {
// calculate_and_store_package_best_fit($order->get_id());
// }


// Requires BoxPacker 4.1.0 (https://github.com/dvdoug/BoxPacker) to be installed in the "wp-content" folder (no Composer needed)
require_once WP_CONTENT_DIR . '/boxpacker/src/Box.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/BoxList.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/BoxSorter.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/DefaultBoxSorter.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/Item.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/ItemList.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/ItemSorter.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/DefaultItemSorter.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/LayerPacker.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/LayerStabiliser.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/OrientatedItem.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/OrientatedItemFactory.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/OrientatedItemSorter.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/PackedBox.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/PackedBoxList.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/PackedBoxSorter.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/PackedItem.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/PackedItemList.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/PackedLayer.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/DefaultPackedBoxSorter.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/Packer.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/Rotation.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/VolumePacker.php';
require_once WP_CONTENT_DIR . '/boxpacker/src/Exception/NoBoxesAvailableException.php';
// require_once WP_CONTENT_DIR . '/boxpacker/tests/Test/TestBox.php';
// require_once WP_CONTENT_DIR . '/boxpacker/tests/Test/TestItem.php';

use DVDoug\BoxPacker\Box;
use DVDoug\BoxPacker\DefaultItemSorter;
use DVDoug\BoxPacker\Item;
use DVDoug\BoxPacker\ItemSorter;
use DVDoug\BoxPacker\Packer;
use DVDoug\BoxPacker\Rotation;
use DVDoug\BoxPacker\Exception\NoBoxesAvailableException;

// use DVDoug\BoxPacker\Test\TestBox;
// use DVDoug\BoxPacker\Test\TestItem;

add_action($hook_name = 'woocommerce_checkout_order_processed', $callback = 'calculate_and_store_package_best_fit', $priority = 10, $accepted_args = 1);
add_action($hook_name = 'woocommerce_admin_order_data_after_order_details', $callback = 'display_custom_order_meta', $priority = 10, $accepted_args = 1);


class CustomBox implements DVDoug\BoxPacker\Box
{
    public function __construct(
        private string $reference,
        private int $outerWidth,
        private int $outerLength,
        private int $outerDepth,
        private int $emptyWeight,
        private int $innerWidth,
        private int $innerLength,
        private int $innerDepth,
        private int $maxWeight
    ) {
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getOuterWidth(): int
    {
        return $this->outerWidth;
    }

    public function getOuterLength(): int
    {
        return $this->outerLength;
    }

    public function getOuterDepth(): int
    {
        return $this->outerDepth;
    }

    public function getEmptyWeight(): int
    {
        return $this->emptyWeight;
    }

    public function getInnerWidth(): int
    {
        return $this->innerWidth;
    }

    public function getInnerLength(): int
    {
        return $this->innerLength;
    }

    public function getInnerDepth(): int
    {
        return $this->innerDepth;
    }

    public function getMaxWeight(): int
    {
        return $this->maxWeight;
    }
}


class CustomItem implements DVDoug\BoxPacker\Item
{
    public function __construct(
        private string $description,
        private int $width,
        private int $length,
        private int $depth,
        private int $weight
    ) {
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function getAllowedRotation(): Rotation
    {
        return Rotation::BestFit;
    }
}


function calculate_and_store_package_best_fit($order_id)
{
    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    $packer = new Packer(new DefaultItemSorter());

    // Define available boxes
    $packer->addBox(new CustomBox(reference: 'CX ENVIO P', outerWidth: 100, outerLength: 160, outerDepth: 200, emptyWeight: 48, innerWidth: (100 - 3), innerLength: (160 - 3), innerDepth: (200 - 3), maxWeight: 20000));
    $packer->addBox(new CustomBox(reference: 'Box S1', outerWidth: 140, outerLength: 140, outerDepth: 150, emptyWeight: 90, innerWidth: (140 - 3), innerLength: (140 - 3), innerDepth: (150 - 3), maxWeight: 20000));
    $packer->addBox(new CustomBox(reference: 'Box S2', outerWidth: 140, outerLength: 140, outerDepth: 250, emptyWeight: 101, innerWidth: (140 - 3), innerLength: (140 - 3), innerDepth: (250 - 3), maxWeight: 20000));
    $packer->addBox(new CustomBox(reference: 'Box S3', outerWidth: 140, outerLength: 140, outerDepth: 350, emptyWeight: 118, innerWidth: (140 - 3), innerLength: (140 - 3), innerDepth: (350 - 3), maxWeight: 20000));
    $packer->addBox(new CustomBox(reference: 'Box M', outerWidth: 250, outerLength: 350, outerDepth: 150, emptyWeight: 172, innerWidth: (250 - 3), innerLength: (350 - 3), innerDepth: (150 - 3), maxWeight: 20000));
    $packer->addBox(new CustomBox(reference: 'Box L', outerWidth: 380, outerLength: 380, outerDepth: 200, emptyWeight: 316, innerWidth: (380 - 3), innerLength: (380 - 3), innerDepth: (200 - 3), maxWeight: 20000));

    // Add order items to the packer
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if (!$product) {
            continue;
        }

        $length = $product->get_length();
        $width = $product->get_width();
        $height = $product->get_height();
        $weight = $product->get_weight();

        if ($length && $width && $height && $weight) {
            $packer->addItem(new CustomItem(
                description: $product->get_name(),
                width: (int) ($width * 10),   // Convert cm to mm
                length: (int) ($length * 10), // Convert cm to mm
                depth: (int) ($height * 10),  // Convert cm to mm
                weight: (int) ($weight)       // Weight in grams
            ));
        }
    }

    try {

        // Determine the best fit (only the first packed box)
        $packedBoxes = $packer->pack();

        // Convert to an array if necessary
        if ($packedBoxes instanceof Traversable) {
            $packedBoxes = iterator_to_array($packedBoxes);
        }

        if (!empty($packedBoxes)) {
            $packedBox = reset($packedBoxes); // Get the first/smallest box
            $boxType = $packedBox->box;

            // Flatten the array so that keys match the display function
            $package_best_fit = [
                'name'   => $boxType->getReference(),
                'width'  => $boxType->getOuterWidth() / 10,  // Convert mm back to cm
                'length' => $boxType->getOuterLength() / 10, // Convert mm back to cm
                'height' => $boxType->getOuterDepth() / 10, // Convert mm back to cm
                'weight' => $packedBox->getWeight(),
                'items_count' => count($packedBox->items)
            ];

        } else {
            $package_best_fit = ['name' => 'No box fits', 'width' => 0, 'length' => 0, 'height' => 0, 'weight' => 0, 'items_count' => 0];
        }

    } catch (NoBoxesAvailableException $e) {
        $package_best_fit = ['name' => 'No box fits', 'width' => 0, 'length' => 0, 'height' => 0, 'weight' => 0, 'items_count' => 0];
    }

    // Update the order meta with the best fit package
    update_post_meta($order_id, 'order_package_best_fit', json_encode($package_best_fit));

}


// Custom display function to output package best fit meta
function display_custom_order_meta($order)
{
    $package_best_fit = get_post_meta($order->get_id(), 'order_package_best_fit', true);
    if ($package_best_fit) {
        $package_best_fit = json_decode($package_best_fit, true);

        $package_best_fit_name = isset($package_best_fit['name']) ? esc_html($package_best_fit['name']) : '';
        $package_best_fit_length = isset($package_best_fit['length']) ? esc_html($package_best_fit['length']) : '';
        $package_best_fit_width  = isset($package_best_fit['width']) ? esc_html($package_best_fit['width']) : '';
        $package_best_fit_height = isset($package_best_fit['height']) ? esc_html($package_best_fit['height']) : '';
        $package_best_fit_weight = isset($package_best_fit['weight']) ? esc_html($package_best_fit['weight']) : '';

        echo '<div>';
        echo '<p>&nbsp;</p>';
        echo '<h3>Package Best Fit</h3>';
        echo '<p>Package Dimensions (L×W×H) (cm):<br>';
        echo $package_best_fit_name . ' (' . $package_best_fit_length . ' x ' . $package_best_fit_width . ' x ' . $package_best_fit_height . ')</p>';
        echo '<p>Package Weight (g):<br>';
        echo $package_best_fit_weight . '</p>';
        echo '</div>';
    }
}
