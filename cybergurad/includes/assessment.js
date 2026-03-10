document.addEventListener('DOMContentLoaded', function() {
    const assessmentForm = document.querySelector('form');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingText = document.getElementById('loadingText');

    // Rotating messages during loading to make it feel like the AI is working
    const aiSteps = [
        "Identifying device architecture...",
        "Checking known vulnerabilities database...",
        "Analyzing firmware security patterns...",
        "Generating security recommendations...",
        "Finalizing assessment report..."
    ];

    assessmentForm.addEventListener('submit', function(e) {
        const deviceName = document.querySelector('input[name="device_name"]').value.trim();
        
        if (deviceName === "") {
            e.preventDefault();
            alert("Please enter the device name.");
            return;
        }

        // Show loading overlay
        loadingOverlay.style.display = 'flex';

        // Rotate messages periodically
        let step = 0;
        const textInterval = setInterval(() => {
            if (step < aiSteps.length) {
                loadingText.innerHTML = aiSteps[step];
                step++;
            } else {
                clearInterval(textInterval);
            }
        }, 1500); // Change message every 1.5 seconds
    });
});