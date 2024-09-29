<?php
namespace App;

ini_set('memory_limit', '1024M');  // Increase memory limit for large Excel files

require 'vendor/autoload.php';
require 'Database.php';
require 'ProductRepository.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as SpreadsheetException;

class FetchData
{
    private ProductRepository $productRepository;

    public function __construct()
    {
        $this->productRepository = new ProductRepository(Database::getConnection());
    }

    /**
     * Download and parse the Alko price list from a given URL
     * @return array Parsed Excel data as an array
     * @throws \Exception If the download or parsing fails
     */
    public function fetchAlkoPriceList(): array
    {
        // Correct Excel file URL
        $url = 'https://www.alko.fi/INTERSHOP/static/WFS/Alko-OnlineShop-Site/-/Alko-OnlineShop/fi_FI/Alkon%20Hinnasto%20Tekstitiedostona/alkon-hinnasto-tekstitiedostona.xlsx';

        try {
            $fileContents = $this->downloadFile($url);  // Download file contents
            return $this->parseExcelFileFromString($fileContents);  // Parse the file contents
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch and parse Alko price list: " . $e->getMessage());
        }
    }

    /**
     * Download file content from a URL using a stream context
     * @param string $url The file URL to download
     * @return string The content of the downloaded file
     * @throws \Exception If the download fails or content is not an Excel file
     */
    private function downloadFile(string $url): string
    {
        // Set user agent to simulate a real browser request
        $contextOptions = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: PHP\r\n",
            ],
        ];
        $context = stream_context_create($contextOptions);

        // Check headers to validate file type before download
        $headers = get_headers($url, 1);
        if (!isset($headers['Content-Type']) || stripos($headers['Content-Type'], 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') === false) {
            throw new \Exception("The file is not an Excel file. Received content type: " . $headers['Content-Type']);
        }

        // Download the file contents
        $fileContents = file_get_contents($url, false, $context);
        if ($fileContents === false) {
            throw new \Exception("Failed to download the file from URL: $url");
        }

        return $fileContents;
    }

    /**
     * Parse an Excel file from raw string data
     * @param string $fileContents The raw file content as a string
     * @return array Parsed Excel data as an array
     * @throws \Exception If parsing fails
     */
    private function parseExcelFileFromString(string $fileContents): array
    {
        // Create a temporary file to store the Excel file contents
        $tempFilePath = tempnam(sys_get_temp_dir(), 'alko_') . '.xlsx';
        file_put_contents($tempFilePath, $fileContents);

        // Load and parse the Excel file
        try {
            $spreadsheet = IOFactory::load($tempFilePath);
            $worksheet = $spreadsheet->getActiveSheet();
            unlink($tempFilePath);  // Clean up the temporary file
            return $worksheet->toArray();
        } catch (SpreadsheetException $e) {
            unlink($tempFilePath);  // Ensure the file is deleted on failure
            throw new \Exception("Failed to parse the Excel file: " . $e->getMessage());
        }
    }

    /**
     * Fetch the current exchange rate from Euros to GBP
     * @return float EUR to GBP exchange rate
     */
    public function fetchCurrencyRate(): float
    {
        $apiKey = '5be0e72f1e8119d9b2eb35f82cc8b2e0';
        $response = file_get_contents("http://apilayer.net/api/live?access_key=$apiKey&currencies=GBP&source=EUR&format=1");
        $data = json_decode($response, true);
        $conversionRate = $data['quotes']['EURGBP'] ?? 0.85;  // Fallback value in case of missing data
        echo "Current EUR to GBP exchange rate: $conversionRate\n";
        return $conversionRate;  // Fallback value in case of missing data
    }

    /**
     * Update the database with the parsed product data
     * @param array $priceList The parsed Excel data
     * @param float $eurToGbp Exchange rate from EUR to GBP
     */
    public function updateDatabase(array $priceList, float $eurToGbp): void
    {
        foreach ($priceList as $row) {
            if (isset($row[0]) && is_numeric($row[0])) {  // Check if row contains a valid product number
                $productNumber = $row[0];
                $productName = $row[1];
                $bottleSize = $row[2];
                $price = $row[3];

                // Handle empty or null prices by setting a default value (e.g., 0.00)
                if (is_null($price) || trim($price) === '') {
                    echo "Empty price for product {$productName}, setting price to 0.00\n";
                    $cleanedPrice = 0.00;
                } else {
                    // Extract numeric values from the price field
                    $cleanedPrice = $this->extractNumericValue($price);
                    if ($cleanedPrice === null) {
                        echo "Skipping non-numeric price for product {$productName} with value: {$price}\n";
                        continue;
                    }
                }

                $priceGBP = round($cleanedPrice * $eurToGbp, 2);  // Convert EUR price to GBP

                $productData = [
                    'number' => $productNumber,
                    'name' => $productName,
                    'bottlesize' => $bottleSize,
                    'price' => $cleanedPrice,
                    'priceGBP' => $priceGBP,
                ];

                // Insert or update the product in the database
                $this->productRepository->upsertProduct($productData);
            }
        }
    }

    /**
     * Extract numeric value from the price field
     * @param string $value The value that contains non-numeric data
     * @return float|null Cleaned numeric value or null if invalid
     */
    private function extractNumericValue(?string $value): ?float
    {
        // Remove non-numeric characters like 'l' for liters
        $cleanedValue = preg_replace('/[^0-9,\.]/', '', $value);  // Remove non-numeric characters

        // Replace commas with dots for proper decimal handling
        $cleanedValue = str_replace(',', '.', $cleanedValue);

        // Check if the cleaned value is a valid number
        if (is_numeric($cleanedValue)) {
            return (float)$cleanedValue;
        }
        return null;
    }

    /**
     * Main method to execute the fetching, parsing, and updating process
     */
    public function run(): void
    {
        try {
            $priceList = $this->fetchAlkoPriceList();  // Fetch the Alko price list
            $eurToGbp = $this->fetchCurrencyRate();  // Fetch the current exchange rate
            $this->updateDatabase($priceList, $eurToGbp);  // Update the database
            echo "Database updated successfully!";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage();  // Display any error encountered
        }
    }
}

$fetchData = new FetchData();
$fetchData->run();
