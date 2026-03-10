<?php
    $pageTitle = "CyberGuard IoT - Result";
    include 'header.php';


    // 2. Include the database connection
    require 'db_connect.php';

// 3. Get the assessment ID from the URL and validate it
$assessmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($assessmentId > 0) {
    // 4. Prepare the SQL query to get data from both tables using a JOIN
    $sql = "SELECT 
                d.device_name, 
                d.manufacturer, 
                d.model,
                a.status, 
                a.summary, 
                a.recommendations, 
                a.proper_usage
            FROM assessments a
            JOIN devices d ON a.device_id = d.id
            WHERE a.id = ?";

    // 5. Use a prepared statement for security
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $assessmentId);
    $stmt->execute();
    $result = $stmt->get_result();

    // 6. Check if we found a result
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        // Assign data to variables for easier use in HTML
        $deviceName = htmlspecialchars($data['device_name']);
        $manufacturer = htmlspecialchars($data['manufacturer']);
        $model = htmlspecialchars($data['model']);
        $status = htmlspecialchars($data['status']);
        $summary = nl2br(htmlspecialchars($data['summary']));
        $recommendations = $data['recommendations'];
        $proper_usage = $data['proper_usage'];

        // Sanitize HTML from AI before rendering to avoid XSS (basic sanitizer)
        function sanitize_html($html) {
            if (trim($html) === '') return '';
            libxml_use_internal_errors(true);
            $doc = new DOMDocument();
            $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            // Remove script and style tags
            $xp = new DOMXPath($doc);
            foreach ($xp->query('//script|//style') as $n) { $n->parentNode->removeChild($n); }

            // Remove event handlers and javascript: URIs
            foreach ($doc->getElementsByTagName('*') as $el) {
                // remove on* attributes
                $attrs = [];
                foreach ($el->attributes as $a) { $attrs[] = $a->name; }
                foreach ($attrs as $an) {
                    if (stripos($an, 'on') === 0) { $el->removeAttribute($an); }
                }
                // sanitize href/src
                if ($el->hasAttribute('href')) {
                    $href = $el->getAttribute('href');
                    if (preg_match('/^\s*javascript:/i', $href)) { $el->removeAttribute('href'); }
                }
            }
            $out = $doc->saveHTML();
            return $out;
        }

        $recommendations = sanitize_html($recommendations);
        $proper_usage = sanitize_html($proper_usage);

        // --- Start of HTML content ---
        ?>
        <section class="container" style="padding-top:50px;">

            <h1 class="section-title">🧠 AI Security Assessment Result</h1>
            <p class="text-muted" style="margin-bottom:30px;">
                Below is the AI-generated security analysis based on the provided device information.
            </p>

            <!-- ====== Device Overview ====== -->
            <div class="card">
                <h2 class="section-title">📦 Device Overview</h2>
                <p><strong>Device Name:</strong> <?php echo $deviceName; ?></p>
                <p><strong>Manufacturer:</strong> <?php echo $manufacturer; ?></p>
                <p><strong>Model:</strong> <?php echo $model; ?></p>
            </div>

            <!-- ====== Security Summary ====== -->
            <div class="card" style="margin-top:24px;">
                <h2 class="section-title">🔐 Security Summary</h2>
                <p class="text-muted">
                    <?php echo $summary; ?>
                </p>
            </div>

            <!-- ====== Proper Usage ====== -->
            <div class="card" style="margin-top:24px;">
                <h2 class="section-title">✅ Proper Usage</h2>
                <ul class="text-muted">
                    <?php echo $proper_usage; ?>
                </ul>
            </div>

            <!-- ====== Security Recommendations ====== -->
            <div class="card" style="margin-top:24px;">
                <h2 class="section-title">⚠ Security Recommendations</h2>
                <ul class="text-muted">
                    <?php echo $recommendations; ?>
                </ul>
            </div>

            <a href="assessment.php" class="btn-back" style="margin-top:30px;">
                New Assessment
            </a>

        </section>
        <?php
        // --- End of HTML content ---

    } else {
        // If no assessment with this ID is found
        ?>
        <section class='container' style='padding-top:50px; text-align:center;'>
            <h1>Assessment Not Found</h1>
            <p>The requested assessment could not be found. Please try again.</p>
            <a href='assessment.php' class='btn-primary'>Start a New Assessment</a>
        </section>
        <?php
    }

    // Close the statement
    $stmt->close();

} else {
    // If the ID in the URL is invalid or missing
    ?>
    <section class='container' style='padding-top:50px; text-align:center;'>
        <h1>Invalid Request</h1>
        <p>No assessment ID was provided. Please start an assessment first.</p>
        <a href='assessment.php' class='btn-primary'>Start an Assessment</a>
    </section>
    <?php
}

// Close the connection
$conn->close();


    include 'footer.php';
?>