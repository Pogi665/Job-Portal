<?php 
require_once 'admin_header.php'; 
require_once '../database_connection.php'; // Ensure this path is correct

// Check database connection
if ($conn->connect_error) {
    die("<div class=\"container mx-auto px-4 py-8\"><p class=\"text-red-500\'>Connection failed: " . $conn->connect_error . "</p></div>");
}

// Fetch jobs from the database
$jobs = [];
// Selecting relevant columns. You might want to join with users table to get employer's full name if needed.
$sql = "SELECT id, title, employer_username, company, location, job_type, salary, status, timestamp, rejection_reason FROM jobs ORDER BY timestamp DESC";
$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $jobs[] = $row;
        }
    }
} else {
    // It's good to log errors or display them during development
    $_SESSION['job_action_message'] = "Error fetching jobs: " . $conn->error;
    $_SESSION['job_action_message_type'] = 'error';
}

?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6 pb-2 border-b-2 border-gray-300">
        <h1 class="text-3xl font-bold text-gray-800">Manage Job Postings</h1>
    </div>

    <p class="mb-6 text-gray-600 text-sm">This section allows administrators to view, edit, approve, or delete job postings. You can also perform bulk actions on selected jobs.</p>

    <?php 
    // For displaying messages from actions like delete_job.php (if we implement it)
    if (isset($_SESSION['job_action_message'])) {
        $message_text = htmlspecialchars($_SESSION['job_action_message']);
        $message_type = htmlspecialchars($_SESSION['job_action_message_type'] ?? 'info');
        $alert_class = '';
        switch ($message_type) {
            case 'success': $alert_class = 'bg-green-100 border-green-400 text-green-700'; break;
            case 'error':   $alert_class = 'bg-red-100 border-red-400 text-red-700'; break;
            case 'warning': $alert_class = 'bg-yellow-100 border-yellow-400 text-yellow-700'; break;
            default:        $alert_class = 'bg-blue-100 border-blue-400 text-blue-700'; break;
        }
        echo "<div class=\"mb-4 px-4 py-3 rounded relative border {$alert_class}\" role=\"alert\"><span class=\"block sm:inline\">{$message_text}</span></div>";
        unset($_SESSION['job_action_message']);
        unset($_SESSION['job_action_message_type']);
    }
    ?>

    <?php if (!empty($jobs)): ?>
    <form action="process_bulk_jobs.php" method="POST" id="bulkJobsForm">
        <div class="mb-4 flex items-center">
            <label for="bulk_action" class="mr-2 text-sm font-medium text-gray-700">With selected:</label>
            <select name="bulk_action" id="bulk_action" class="form-select rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                <option value="">Choose action...</option>
                <option value="approve">Approve</option>
                <option value="reject">Reject (No Reason)</option>
                <option value="reject_with_reason">Reject (With Reason - separate step)</option> <!-- This will need different handling -->
                <option value="delete">Delete</option>
            </select>
            <button type="submit" name="apply_bulk_action" class="ml-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md text-sm transition duration-150 ease-in-out">
                Apply
            </button>
        </div>

        <div class="bg-white shadow-md rounded-lg overflow-x-auto">
            <table class="min-w-full leading-normal">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <input type="checkbox" id="selectAllJobs" class="form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out">
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Title</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Employer</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Posted On</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    <?php foreach ($jobs as $job): 
                        $row_bg_class = 'bg-white';
                        $status_text_class = 'text-gray-700';
                        if ($job['status'] === 'pending_approval') {
                            $row_bg_class = 'bg-yellow-50'; 
                            $status_text_class = 'text-yellow-700 font-semibold';
                        } elseif ($job['status'] === 'rejected') {
                            $row_bg_class = 'bg-red-50'; 
                            $status_text_class = 'text-red-700 font-semibold';
                        } elseif ($job['status'] === 'active') {
                            $status_text_class = 'text-green-700 font-semibold';
                        }
                    ?>
                    <tr class="<?php echo $row_bg_class; ?> hover:bg-gray-50 border-b border-gray-200">
                        <td class="px-5 py-4"><input type="checkbox" name="job_ids[]" value="<?php echo $job['id']; ?>" class="job-checkbox form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out"></td>
                        <td class="px-5 py-4"><?php echo htmlspecialchars($job['id']); ?></td>
                        <td class="px-5 py-4">
                            <p class="font-medium"><?php echo htmlspecialchars($job['title']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($job['company'] ?? 'N/A'); ?> - <?php echo htmlspecialchars($job['location'] ?? 'N/A'); ?></p>
                        </td>
                        <td class="px-5 py-4"><?php echo htmlspecialchars($job['employer_username']); ?></td>
                        <td class="px-5 py-4">
                            <span class="<?php echo $status_text_class; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_',' ',$job['status'] ?? 'N/A'))); ?></span>
                            <?php if ($job['status'] === 'rejected' && !empty($job['rejection_reason'])): ?>
                                <p class="text-xs text-gray-500 mt-1"><em>Reason: <?php echo htmlspecialchars($job['rejection_reason']); ?></em></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4"><?php echo htmlspecialchars(date("M d, Y", strtotime($job['timestamp']))); ?></td>
                        <td class="px-5 py-4 text-center whitespace-nowrap">
                            <a href="../view_job_details_admin.php?job_id=<?php echo $job['id']; ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900 text-xs mr-2">View</a>
                            <a href="edit_job_admin.php?id=<?php echo $job['id']; ?>" class="text-blue-600 hover:text-blue-900 text-xs mr-2">Edit</a>
                            <?php if ($job['status'] === 'pending_approval'): ?>
                                <a href="approve_job.php?id=<?php echo $job['id']; ?>" class="text-green-600 hover:text-green-900 text-xs mr-2">Approve</a>
                                <a href="reject_job_reason.php?job_id=<?php echo $job['id']; ?>" class="text-red-600 hover:text-red-900 text-xs">Reject</a>
                            <?php else: ?>
                                <a href="delete_job_admin.php?id=<?php echo $job['id']; ?>" class="text-red-600 hover:text-red-900 text-xs" onclick="return confirm('Are you sure you want to delete this job posting? This action cannot be undone.');">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
    <?php else: ?>
        <div class="text-center py-12">
             <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">No job postings found</h3>
            <p class="mt-1 text-sm text-gray-500">There are currently no job postings in the system.</p>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAllJobs');
    const jobCheckboxes = document.querySelectorAll('.job-checkbox');

    if(selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function(e) {
            jobCheckboxes.forEach(checkbox => {
                checkbox.checked = e.target.checked;
            });
        });
    }

    jobCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            }
            // Optional: check if all are checked to update selectAllCheckbox
            // let allChecked = true;
            // jobCheckboxes.forEach(cb => { if(!cb.checked) allChecked = false; });
            // selectAllCheckbox.checked = allChecked;
        });
    });

    const bulkForm = document.getElementById('bulkJobsForm');
    if(bulkForm) {
        bulkForm.addEventListener('submit', function(e){
            const action = document.getElementById('bulk_action').value;
            const selectedJobs = document.querySelectorAll('.job-checkbox:checked').length;

            if (selectedJobs === 0) {
                alert('Please select at least one job to apply the bulk action.');
                e.preventDefault();
                return;
            }

            if (!action) {
                alert('Please select a bulk action to perform.');
                e.preventDefault();
                return;
            }

            let confirmMessage = 'Are you sure you want to ' + action + ' the selected ' + selectedJobs + ' job(s)?';
            if (action === 'delete') {
                confirmMessage += '\nTHIS ACTION CANNOT BE UNDONE.';
            }
            if (action === 'reject_with_reason'){
                alert('Bulk rejecting with reason requires individual review. Please use the single reject action for each job to provide a reason, or choose "Reject (No Reason)" for bulk action.');
                e.preventDefault();
                return;
            }

            if (!confirm(confirmMessage)) {
                e.preventDefault();
            }
        });
    }
});
</script>

<?php 
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
require_once 'admin_footer.php'; 
?> 