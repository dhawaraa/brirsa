<?php
/**
 * Admin Research Evaluation / Checklist V2 (Single Request View & Edit)
 */
$pageTitle = "แบบตรวจรับรองงานวิจัย";
require_once '../includes/config.php';

// Access Control
check_role(['super_admin', 'data_admin', 'committee']);

$id = intval($_GET['id'] ?? 0);
$pdo = getDB();

// Fetch request
$stmt = $pdo->prepare("SELECT * FROM requests WHERE id = ?");
$stmt->execute([$id]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: checklists.php');
    exit;
}

// Fetch existing evaluation
$stmtEval = $pdo->prepare("SELECT * FROM request_evaluations WHERE request_id = ? ORDER BY id DESC LIMIT 1");
$stmtEval->execute([$id]);
$eval = $stmtEval->fetch();

$message = '';
$messageType = '';

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_checklist') {
    try {
        // Check permissions
        $canEditSec2 = is_role('super_admin') || is_role('data_admin');
        $canEditSec345 = is_role('super_admin') || is_role('committee');

        // Common General info (Section 1)
        $evalDate = sanitize($_POST['eval_date'] ?? date('Y-m-d'));
        $evaluatorName = sanitize($_POST['evaluator_name'] ?? '');
        $directorName = sanitize($_POST['director_name'] ?? '');

        // หมวด ก (Section 2: Data Admin / Super Admin)
        $docReport = $canEditSec2 ? sanitize($_POST['doc_report'] ?? '') : ($eval['doc_report'] ?? '');
        $docPlagiarism = $canEditSec2 ? sanitize($_POST['doc_plagiarism'] ?? '') : ($eval['doc_plagiarism'] ?? '');
        $docCert = $canEditSec2 ? sanitize($_POST['doc_cert'] ?? '') : ($eval['doc_cert'] ?? '');
        $docParticipation = $canEditSec2 ? sanitize($_POST['doc_participation'] ?? '') : ($eval['doc_participation'] ?? '');
        $docEthics = $canEditSec2 ? sanitize($_POST['doc_ethics'] ?? '') : ($eval['doc_ethics'] ?? '');

        // หมวด คุณภาพ (Section 3: Committee / Super Admin)
        $qConsistent = $canEditSec345 ? sanitize($_POST['q_consistent'] ?? '') : ($eval['q_consistent'] ?? '');
        $qLiterature = $canEditSec345 ? sanitize($_POST['q_literature'] ?? '') : ($eval['q_literature'] ?? '');
        $qMethodology = $canEditSec345 ? sanitize($_POST['q_methodology'] ?? '') : ($eval['q_methodology'] ?? '');
        $qToolValidity = $canEditSec345 ? sanitize($_POST['q_tool_validity'] ?? '') : ($eval['q_tool_validity'] ?? '');
        $qKnowledge = $canEditSec345 ? sanitize($_POST['q_knowledge'] ?? '') : ($eval['q_knowledge'] ?? '');

        // หมวด AI (Section 3: Committee / Super Admin)
        $aiAcceptableUse = $canEditSec345 ? sanitize($_POST['ai_acceptable_use'] ?? '') : ($eval['ai_acceptable_use'] ?? '');
        $aiVerifiedCitations = $canEditSec345 ? sanitize($_POST['ai_verified_citations'] ?? '') : ($eval['ai_verified_citations'] ?? '');
        $aiResearcherAnalysis = $canEditSec345 ? sanitize($_POST['ai_researcher_analysis'] ?? '') : ($eval['ai_researcher_analysis'] ?? '');

        // ส่วนที่ ๔ ข้อสังเกต (Section 4: Committee / Super Admin)
        $issue1 = $canEditSec345 ? sanitize($_POST['issue_1'] ?? '') : ($eval['issue_1'] ?? '');
        $issue2 = $canEditSec345 ? sanitize($_POST['issue_2'] ?? '') : ($eval['issue_2'] ?? '');
        $issue3 = $canEditSec345 ? sanitize($_POST['issue_3'] ?? '') : ($eval['issue_3'] ?? '');
        $issue4 = $canEditSec345 ? sanitize($_POST['issue_4'] ?? '') : ($eval['issue_4'] ?? '');
        $additionalComments = $canEditSec345 ? sanitize($_POST['additional_comments'] ?? '') : ($eval['additional_comments'] ?? '');

        // ส่วนที่ ๕ ผลพิจารณา (Section 5: Committee / Super Admin)
        $resultDecision = $canEditSec345 ? sanitize($_POST['result_decision'] ?? '') : ($eval['result_decision'] ?? '');
        $deadlineDate = $canEditSec345 ? (!empty($_POST['deadline_date']) ? sanitize($_POST['deadline_date']) : null) : ($eval['deadline_date'] ?? null);
        $rejectionReason = $canEditSec345 ? sanitize($_POST['rejection_reason'] ?? '') : ($eval['rejection_reason'] ?? '');

        $currentUserId = $_SESSION['admin_id'] ?? null;

        if ($eval) {
            // Update
            $updateSql = "UPDATE request_evaluations SET 
                eval_date = ?, evaluator_name = ?, director_name = ?,
                doc_report = ?, doc_plagiarism = ?, doc_cert = ?, doc_participation = ?, doc_ethics = ?,
                q_consistent = ?, q_literature = ?, q_methodology = ?, q_tool_validity = ?, q_knowledge = ?,
                ai_acceptable_use = ?, ai_verified_citations = ?, ai_researcher_analysis = ?,
                issue_1 = ?, issue_2 = ?, issue_3 = ?, issue_4 = ?, additional_comments = ?,
                result_decision = ?, deadline_date = ?, rejection_reason = ?,
                updated_at = NOW()
                WHERE id = ?";
            $stmtUp = $pdo->prepare($updateSql);
            $stmtUp->execute([
                $evalDate, $evaluatorName, $directorName,
                $docReport, $docPlagiarism, $docCert, $docParticipation, $docEthics,
                $qConsistent, $qLiterature, $qMethodology, $qToolValidity, $qKnowledge,
                $aiAcceptableUse, $aiVerifiedCitations, $aiResearcherAnalysis,
                $issue1, $issue2, $issue3, $issue4, $additionalComments,
                $resultDecision, $deadlineDate, $rejectionReason,
                $eval['id']
            ]);
        } else {
            // Insert
            $insertSql = "INSERT INTO request_evaluations (
                request_id, eval_date, evaluator_name, director_name,
                doc_report, doc_plagiarism, doc_cert, doc_participation, doc_ethics,
                q_consistent, q_literature, q_methodology, q_tool_validity, q_knowledge,
                ai_acceptable_use, ai_verified_citations, ai_researcher_analysis,
                issue_1, issue_2, issue_3, issue_4, additional_comments,
                result_decision, deadline_date, rejection_reason, created_by
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?
            )";
            $stmtIn = $pdo->prepare($insertSql);
            $stmtIn->execute([
                $id, $evalDate, $evaluatorName, $directorName,
                $docReport, $docPlagiarism, $docCert, $docParticipation, $docEthics,
                $qConsistent, $qLiterature, $qMethodology, $qToolValidity, $qKnowledge,
                $aiAcceptableUse, $aiVerifiedCitations, $aiResearcherAnalysis,
                $issue1, $issue2, $issue3, $issue4, $additionalComments,
                $resultDecision, $deadlineDate, $rejectionReason, $currentUserId
            ]);
        }

        // Automatic Status Syncing with requests table
        $newStatus = null;
        $statusNote = '';

        if ($canEditSec345 && !empty($resultDecision)) {
            // มติของคณะกรรมการในส่วนที่ ๕ เป็นตัวชี้ขาดสถานะหลัก
            if ($resultDecision === 'certified') {
                $newStatus = 'approved';
                $statusNote = $additionalComments ?: 'ผ่านการประเมินและรับรองคุณภาพงานวิจัย';
            } elseif ($resultDecision === 'certified_with_condition') {
                $newStatus = 'correction';
                $issues = array_filter([$issue1, $issue2, $issue3, $issue4]);
                $issueText = !empty($issues) ? implode("\n", $issues) : '';
                $statusNote = $issueText ?: ($additionalComments ?: 'รับรองโดยมีเงื่อนไข (ต้องแก้ไขตามข้อสังเกต)');
                if ($deadlineDate) {
                    $statusNote .= " (กำหนดส่งคืนภายใน: " . $deadlineDate . ")";
                }
            } elseif ($resultDecision === 'rejected') {
                $newStatus = 'rejected';
                $statusNote = $rejectionReason ?: ($additionalComments ?: 'ไม่รับรองคุณภาพงานวิจัย');
            }
        } elseif ($canEditSec2) {
            // กรณีเจ้าหน้าที่ตรวจเอกสารหมวด ก (ส่วนที่ ๒)
            $docItems = [$docReport, $docPlagiarism, $docCert, $docParticipation, $docEthics];
            $hasFailOrEdit = in_array('fail', $docItems) || in_array('edit', $docItems);
            $allPassed = !in_array('', $docItems, true) && count(array_unique($docItems)) === 1 && $docItems[0] === 'pass';

            if ($hasFailOrEdit) {
                // หากมีเอกสารไม่ผ่าน หรือให้แก้ไข -> ส่งกลับให้นักวิจัยแก้ไขเอกสาร
                $newStatus = 'ch_edit';
                $statusNote = 'เอกสารไม่ครบถ้วนหรือไม่สมบูรณ์ ให้ตรวจสอบและแนบเอกสารเพิ่มเติมตามรายการตรวจสอบ';
            } elseif ($allPassed && in_array($request['status'], ['checking', 'resubmitted', 'ch_edit'])) {
                // หากเอกสารผ่านครบทั้ง ๕ ข้อ -> ปรับสถานะส่งต่อให้คณะกรรมการพิจารณา
                $newStatus = 'committee';
                $statusNote = 'เอกสารครบถ้วนสมบูรณ์ ส่งต่อคณะกรรมการตรวจสอบรับรอง';
            }
        }

        if ($newStatus) {
            $syncStmt = $pdo->prepare("UPDATE requests SET status = ?, correction_note = ?, updated_at = NOW() WHERE id = ?");
            $syncStmt->execute([$newStatus, $statusNote, $id]);
            // อัปเดตตัวแปร request ให้แสดงสถานะใหม่ทันที
            $request['status'] = $newStatus;
            $request['correction_note'] = $statusNote;
        }

        $message = "บันทึกแบบตรวจรับรองเรียบร้อยแล้ว";
        $messageType = "success";

        // Re-fetch
        $stmtEval->execute([$id]);
        $eval = $stmtEval->fetch();

    } catch (Exception $e) {
        $message = "เกิดข้อผิดพลาด: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Helpers for Checklist Radio Group
function renderCheckRadio($name, $currentVal, $disabled = false) {
    $options = [
        'pass' => [
            'label' => 'ผ่าน',
            'icon' => '<svg class="w-3.5 h-3.5 me-1 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>',
            'class' => 'text-emerald-700 bg-emerald-50 border-emerald-300 dark:bg-emerald-950 dark:border-emerald-800'
        ],
        'fail' => [
            'label' => 'ไม่ผ่าน',
            'icon' => '<svg class="w-3.5 h-3.5 me-1 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>',
            'class' => 'text-rose-700 bg-rose-50 border-rose-300 dark:bg-rose-950 dark:border-rose-800'
        ],
        'edit' => [
            'label' => 'ให้แก้ไข',
            'icon' => '<svg class="w-3.5 h-3.5 me-1 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
            'class' => 'text-amber-700 bg-amber-50 border-amber-300 dark:bg-amber-950 dark:border-amber-800'
        ],
    ];

    $html = '<div class="inline-flex space-x-2 text-xs">';
    foreach ($options as $val => $opt) {
        $checked = (!empty($currentVal) && $currentVal === $val) ? 'checked' : '';
        $disabledAttr = $disabled ? 'disabled' : '';
        $opacityClass = $disabled ? ($checked ? 'opacity-100 ring-1 ring-offset-1 ring-gray-400 cursor-not-allowed' : 'opacity-40 cursor-not-allowed') : 'cursor-pointer hover:opacity-80';
        $html .= '<label class="flex items-center px-2.5 py-1.5 rounded border transition ' . $opt['class'] . ' ' . $opacityClass . '">';
        $html .= '<input type="radio" name="' . htmlspecialchars($name) . '" value="' . $val . '" ' . $checked . ' ' . $disabledAttr . ' class="w-3.5 h-3.5 text-blue-600 focus:ring-blue-500">';
        $html .= '<span class="ms-1.5 font-medium whitespace-nowrap inline-flex items-center">' . $opt['icon'] . $opt['label'] . '</span>';
        $html .= '</label>';
    }
    $html .= '</div>';
    return $html;
}

// Role Permissions for Checklist sections
$isSuperAdmin = is_role('super_admin');
$isDataAdmin = is_role('data_admin');
$isCommittee = is_role('committee');

// Section 2 (Category A - Docs): Super Admin & Data Admin
$canEditSec2 = $isSuperAdmin || $isDataAdmin;

// Section 3 (Quality & AI), Section 4 (Issues/Observations), Section 5 (Final Decision): Committee & Super Admin
$canEditSec345 = $isSuperAdmin || $isCommittee;

include 'includes/header.php';
?>

<!-- Breadcrumb & Top Bar -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 mb-4 border-b border-gray-200 dark:border-gray-700">
            <div>
                <nav class="flex mb-2" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-2">
                        <li class="inline-flex items-center">
                            <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 dark:text-gray-300">หน้าหลัก</a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                                <a href="checklists.php" class="ms-1 text-gray-700 hover:text-blue-600 dark:text-gray-300">แบบตรวจรับรอง (Checklist)</a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                                <span class="ms-1 text-gray-500 font-semibold dark:text-gray-400"><?php echo htmlspecialchars($request['request_id']); ?></span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                            d="M2 6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6Zm4.996 2a1 1 0 0 0 0 2h.01a1 1 0 1 0 0-2h-.01Zm0 3a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2h-.01Zm0 3a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2h-.01Zm3-6a1 1 0 0 0 0 2h7.01a1 1 0 1 0 0-2H9.996Zm0 3a1 1 0 1 0 0 2h7.01a1 1 0 1 0 0-2H9.996Zm0 3a1 1 0 1 0 0 2h7.01a1 1 0 1 0 0-2H9.996Z"
                            clip-rule="evenodd" />
                    </svg>
                    <span>แบบตรวจรับรองงานวิจัยเพื่อขอตำแหน่งทางวิชาการ</span>
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    สถาบันวิจัยพุทธศาสตร์ มหาวิทยาลัยมหาจุฬาลงกรณราชวิทยาลัย
                </p>
            </div>
            <div class="mt-4 sm:mt-0 flex flex-wrap gap-2">
                <a href="view.php?id=<?php echo $id; ?>" class="px-3.5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 inline-flex items-center">
                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    กลับหน้ารายละเอียด
                </a>
                <a href="checklist_print.php?id=<?php echo $id; ?>" target="_blank" class="px-3.5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 inline-flex items-center shadow-sm">
                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    พิมพ์เอกสาร / PDF (ฟอร์มทางการ)
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="evalForm">
            <input type="hidden" name="action" value="save_checklist">

            <!-- ส่วนที่ ๑: ข้อมูลทั่วไป -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6">
                <div class="border-b border-gray-100 dark:border-gray-700 pb-2 mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 me-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        ส่วนที่ ๑ ข้อมูลทั่วไป
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400">สถานะปัจจุบัน:</span>
                        <?php 
                        $curSt = $request['status'] ?? 'checking';
                        $stInfo = $STATUS_OPTIONS[$curSt] ?? ['label' => $curSt, 'color' => 'gray'];
                        $badgeClass = match ($stInfo['color']) {
                            'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
                            'green' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
                            'orange' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300',
                            'red' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
                            'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300',
                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                        };
                        ?>
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full <?php echo $badgeClass; ?>">
                            <?php echo htmlspecialchars($stInfo['label']); ?>
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 block text-xs">รหัสคำขอ:</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400 text-base"><?php echo htmlspecialchars($request['request_id']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 block text-xs">ชื่อ-สกุล ผู้ขอรับรอง:</span>
                        <span class="font-semibold text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($request['name']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 block text-xs">ตำแหน่งที่ขอ:</span>
                        <span class="inline-block px-2.5 py-0.5 rounded font-bold text-xs bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                            <?php echo htmlspecialchars($request['academic_position'] ?: '-'); ?>
                        </span>
                    </div>
                    <div class="md:col-span-2">
                        <span class="text-gray-500 dark:text-gray-400 block text-xs">ชื่องานวิจัย:</span>
                        <span class="font-medium text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($request['project_title']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 block text-xs">สาขาวิชา / สังกัด:</span>
                        <span class="text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars(($request['branch'] ? $request['branch'] . ' / ' : '') . $request['affiliation']); ?></span>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">วันที่ตรวจรับรอง:</label>
                        <input type="date" name="eval_date" value="<?php echo htmlspecialchars($eval['eval_date'] ?? date('Y-m-d')); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">ผู้ตรวจรับรอง / กลั่นกรอง:</label>
                        <input type="text" name="evaluator_name" value="<?php echo htmlspecialchars($eval['evaluator_name'] ?? ($_SESSION['admin_name'] ?? '')); ?>" placeholder="ระบุชื่อผู้ตรวจ" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">ผู้อำนวยการ / ประธานกรรมการ:</label>
                        <input type="text" name="director_name" value="<?php echo htmlspecialchars($eval['director_name'] ?? 'ผู้อำนวยการสถาบันวิจัยพุทธศาสตร์'); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </div>
            </div>

            <!-- ส่วนที่ ๒: รายการตรวจสอบ (Checklist หมวด ก) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 dark:border-gray-700 pb-2 mb-4 gap-2">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 text-amber-500 me-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 2.586A2 2 0 0012.586 2H9z"></path>
                            <path d="M3 8V4a1 1 0 011-1h2v10H4a1 1 0 01-1-1V8z"></path>
                        </svg>
                        ส่วนที่ ๒ รายการตรวจสอบ: หมวด ก — ความครบถ้วนของเอกสาร
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-medium <?php echo $canEditSec2 ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'; ?>">
                            <?php echo $canEditSec2 ? 'สำหรับ: ผู้ดูแลข้อมูล / ผู้ดูแลระบบ' : 'เฉพาะดูข้อมูล (สิทธิ์ผู้ดูแล)'; ?>
                        </span>
                        <span class="text-xs text-gray-400">๕ รายการหลัก</span>
                    </div>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="text-sm">
                            <span class="font-medium text-gray-900 dark:text-white">๑. เล่มรายงานวิจัยฉบับสมบูรณ์</span>
                            <?php if (!empty($request['file_report'])): ?>
                                <a href="../<?php echo htmlspecialchars($request['file_report']); ?>" target="_blank" class="ms-2 text-xs text-blue-600 hover:text-blue-800 font-medium hover:underline inline-flex items-center">
                                    <svg class="w-3.5 h-3.5 me-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    เปิดดูไฟล์
                                </a>
                            <?php else: ?><span class="ms-2 text-xs text-red-500">(ยังไม่มีไฟล์)</span><?php endif; ?>
                        </div>
                        <?php echo renderCheckRadio('doc_report', $eval['doc_report'] ?? '', !$canEditSec2); ?>
                    </div>

                    <div class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="text-sm">
                            <span class="font-medium text-gray-900 dark:text-white">๒. ผลตรวจอักขราวิสุทธิ์</span>
                            <?php if ($request['plagiarism_percent'] !== null): ?>
                                <span class="ms-2 text-xs px-2 py-0.5 bg-gray-100 text-gray-700 rounded dark:bg-gray-700 dark:text-gray-300 font-bold"><?php echo htmlspecialchars($request['plagiarism_percent']); ?>%</span>
                            <?php endif; ?>
                            <?php if (!empty($request['file_plagiarism'])): ?>
                                <a href="../<?php echo htmlspecialchars($request['file_plagiarism']); ?>" target="_blank" class="ms-2 text-xs text-blue-600 hover:text-blue-800 font-medium hover:underline inline-flex items-center">
                                    <svg class="w-3.5 h-3.5 me-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    เปิดดูไฟล์
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php echo renderCheckRadio('doc_plagiarism', $eval['doc_plagiarism'] ?? '', !$canEditSec2); ?>
                    </div>

                    <div class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="text-sm">
                            <span class="font-medium text-gray-900 dark:text-white">๓. บทความวิจัย / แบบตอบรับการตีพิมพ์ (วารสารอยู่ในฐานข้อมูลที่สถาบันยอมรับ)</span>
                            <?php if (!empty($request['file_cert'])): ?>
                                <a href="../<?php echo htmlspecialchars($request['file_cert']); ?>" target="_blank" class="ms-2 text-xs text-blue-600 hover:text-blue-800 font-medium hover:underline inline-flex items-center">
                                    <svg class="w-3.5 h-3.5 me-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    เปิดดูไฟล์
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php echo renderCheckRadio('doc_cert', $eval['doc_cert'] ?? '', !$canEditSec2); ?>
                    </div>

                    <div class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="text-sm">
                            <span class="font-medium text-gray-900 dark:text-white">๔. เอกสารสัดส่วนการมีส่วนร่วม (ลงนามครบทุกคน)</span>
                            <?php if (!empty($request['file_participation'])): ?>
                                <a href="../<?php echo htmlspecialchars($request['file_participation']); ?>" target="_blank" class="ms-2 text-xs text-blue-600 hover:text-blue-800 font-medium hover:underline inline-flex items-center">
                                    <svg class="w-3.5 h-3.5 me-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    เปิดดูไฟล์
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php echo renderCheckRadio('doc_participation', $eval['doc_participation'] ?? '', !$canEditSec2); ?>
                    </div>

                    <div class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="text-sm">
                            <span class="font-medium text-gray-900 dark:text-white">๕. เอกสารรับรองจริยธรรมการวิจัยในมนุษย์</span>
                            <?php if (!empty($request['file_ethics'])): ?>
                                <a href="../<?php echo htmlspecialchars($request['file_ethics']); ?>" target="_blank" class="ms-2 text-xs text-blue-600 hover:text-blue-800 font-medium hover:underline inline-flex items-center">
                                    <svg class="w-3.5 h-3.5 me-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    เปิดดูไฟล์
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php echo renderCheckRadio('doc_ethics', $eval['doc_ethics'] ?? '', !$canEditSec2); ?>
                    </div>
                </div>
            </div>

            <!-- ส่วนที่ ๓: คุณภาพงานวิจัย & การใช้ AI -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 dark:border-gray-700 pb-2 mb-4 gap-2">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 me-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        ส่วนที่ ๓ การรับรองคุณภาพงานวิจัยและการใช้ปัญญาประดิษฐ์
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-medium <?php echo $canEditSec345 ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'; ?>">
                            <?php echo $canEditSec345 ? 'สำหรับ: คณะกรรมการ / ผู้ดูแลระบบ' : 'เฉพาะดูข้อมูล (สิทธิ์คณะกรรมการ)'; ?>
                        </span>
                    </div>
                </div>

                <!-- คุณภาพงานวิจัย -->
                <div class="mb-5">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 me-1.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        ด้านคุณภาพงานวิจัย (๕ ข้อ)
                    </h3>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div class="py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <span class="text-sm text-gray-800 dark:text-gray-200">๑. ชื่อเรื่อง บทคัดย่อ วัตถุประสงค์ และข้อสรุปสอดคล้องกัน</span>
                            <?php echo renderCheckRadio('q_consistent', $eval['q_consistent'] ?? '', !$canEditSec345); ?>
                        </div>
                        <div class="py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <span class="text-sm text-gray-800 dark:text-gray-200">๒. การทบทวนวรรณกรรม ครอบคลุมและทันสมัย อ้างอิงแหล่งที่มาที่มีอยู่จริง</span>
                            <?php echo renderCheckRadio('q_literature', $eval['q_literature'] ?? '', !$canEditSec345); ?>
                        </div>
                        <div class="py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <span class="text-sm text-gray-800 dark:text-gray-200">๓. ระเบียบวิธีวิจัยเหมาะสมกับวัตถุประสงค์</span>
                            <?php echo renderCheckRadio('q_methodology', $eval['q_methodology'] ?? '', !$canEditSec345); ?>
                        </div>
                        <div class="py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <span class="text-sm text-gray-800 dark:text-gray-200">๔. เครื่องมือวิจัยผ่านการตรวจสอบความเที่ยงตรง / ความเชื่อมั่น</span>
                            <?php echo renderCheckRadio('q_tool_validity', $eval['q_tool_validity'] ?? '', !$canEditSec345); ?>
                        </div>
                        <div class="py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <span class="text-sm text-gray-800 dark:text-gray-200">๕. ผลการวิจัยและข้อสรุปมีองค์ความรู้ที่เกิดจากงานวิจัย</span>
                            <?php echo renderCheckRadio('q_knowledge', $eval['q_knowledge'] ?? '', !$canEditSec345); ?>
                        </div>
                    </div>
                </div>

                <!-- การใช้ปัญญาประดิษฐ์ AI -->
                <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-purple-700 dark:text-purple-400 mb-2 flex items-center">
                        <svg class="w-4 h-4 me-1.5 flex-shrink-0 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path>
                        </svg>
                        ด้านการใช้ปัญญาประดิษฐ์ (AI) ในงานวิจัย (๓ ข้อ)
                    </h3>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div class="py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <span class="text-sm text-gray-800 dark:text-gray-200">๑. AI ถูกใช้เฉพาะในระดับที่ยอมรับได้ (ตรวจภาษา/สืบค้น/ร่างเบื้องต้น) ไม่ใช้ AI เขียนข้อมูลหลักหรือสรุปผลแทนผู้วิจัย</span>
                            <?php echo renderCheckRadio('ai_acceptable_use', $eval['ai_acceptable_use'] ?? '', !$canEditSec345); ?>
                        </div>
                        <div class="py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <span class="text-sm text-gray-800 dark:text-gray-200">๒. ข้อมูลและข้อเท็จจริงทุกส่วนได้รับการตรวจสอบโดยผู้วิจัยด้วยตนเอง รวมถึงการอ้างอิง — ไม่มีแหล่งอ้างอิงที่ไม่มีอยู่จริง (Hallucinated Citations)</span>
                            <?php echo renderCheckRadio('ai_verified_citations', $eval['ai_verified_citations'] ?? '', !$canEditSec345); ?>
                        </div>
                        <div class="py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <span class="text-sm text-gray-800 dark:text-gray-200">๓. บทวิเคราะห์ ตีความ และสรุปผลเป็นของผู้วิจัย ไม่ใช่ผลลัพธ์จาก AI โดยตรง</span>
                            <?php echo renderCheckRadio('ai_researcher_analysis', $eval['ai_researcher_analysis'] ?? '', !$canEditSec345); ?>
                        </div>
                    </div>
                    <div class="mt-3 p-3 bg-red-50 dark:bg-red-950/40 rounded-lg text-xs text-red-700 dark:text-red-300 flex items-start">
                        <svg class="w-4 h-4 me-1.5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                        <span><strong>หมายเหตุ:</strong> หากพบว่าผู้ขอรับรองใช้ปัญญาประดิษฐ์ในงานวิจัยมากและแหล่งที่มาผิดมากซึ่งเสี่ยงต่อจริยธรรมการวิจัย ให้ถือ <u>ไม่รับรอง</u></span>
                    </div>
                </div>
            </div>

            <!-- ส่วนที่ ๔: ข้อสังเกตและรายการที่ต้องแก้ไข -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 dark:border-gray-700 pb-2 mb-4 gap-2">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 text-amber-500 me-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        ส่วนที่ ๔ ข้อสังเกตและรายการที่ต้องแก้ไข
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-medium <?php echo $canEditSec345 ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'; ?>">
                            <?php echo $canEditSec345 ? 'สำหรับ: คณะกรรมการ / ผู้ดูแลระบบ' : 'เฉพาะดูข้อมูล (สิทธิ์คณะกรรมการ)'; ?>
                        </span>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">๑. รายการที่ต้องแก้ไข ข้อ ๑:</label>
                            <input type="text" name="issue_1" value="<?php echo htmlspecialchars($eval['issue_1'] ?? ''); ?>" placeholder="ระบุรายละเอียด..." <?php echo !$canEditSec345 ? 'readonly' : ''; ?> class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white <?php echo !$canEditSec345 ? 'bg-gray-100 dark:bg-gray-700/60 cursor-not-allowed opacity-80' : ''; ?>">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">๒. รายการที่ต้องแก้ไข ข้อ ๒:</label>
                            <input type="text" name="issue_2" value="<?php echo htmlspecialchars($eval['issue_2'] ?? ''); ?>" placeholder="ระบุรายละเอียด..." <?php echo !$canEditSec345 ? 'readonly' : ''; ?> class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white <?php echo !$canEditSec345 ? 'bg-gray-100 dark:bg-gray-700/60 cursor-not-allowed opacity-80' : ''; ?>">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">๓. รายการที่ต้องแก้ไข ข้อ ๓:</label>
                            <input type="text" name="issue_3" value="<?php echo htmlspecialchars($eval['issue_3'] ?? ''); ?>" placeholder="ระบุรายละเอียด..." <?php echo !$canEditSec345 ? 'readonly' : ''; ?> class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white <?php echo !$canEditSec345 ? 'bg-gray-100 dark:bg-gray-700/60 cursor-not-allowed opacity-80' : ''; ?>">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">๔. รายการที่ต้องแก้ไข ข้อ ๔:</label>
                            <input type="text" name="issue_4" value="<?php echo htmlspecialchars($eval['issue_4'] ?? ''); ?>" placeholder="ระบุรายละเอียด..." <?php echo !$canEditSec345 ? 'readonly' : ''; ?> class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white <?php echo !$canEditSec345 ? 'bg-gray-100 dark:bg-gray-700/60 cursor-not-allowed opacity-80' : ''; ?>">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">ความเห็นเพิ่มเติม:</label>
                        <textarea name="additional_comments" rows="3" placeholder="ระบุความเห็นเพิ่มเติมของคณะกรรมการ..." <?php echo !$canEditSec345 ? 'readonly' : ''; ?> class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white <?php echo !$canEditSec345 ? 'bg-gray-100 dark:bg-gray-700/60 cursor-not-allowed opacity-80' : ''; ?>"><?php echo htmlspecialchars($eval['additional_comments'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- ส่วนที่ ๕: ผลการพิจารณา -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 dark:border-gray-700 pb-2 mb-4 gap-2">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 me-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        ส่วนที่ ๕ ผลการพิจารณา
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-medium <?php echo $canEditSec345 ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'; ?>">
                            <?php echo $canEditSec345 ? 'สำหรับ: คณะกรรมการ / ผู้ดูแลระบบ' : 'เฉพาะดูข้อมูล (สิทธิ์คณะกรรมการ)'; ?>
                        </span>
                    </div>
                </div>
                <?php $currentDec = $eval['result_decision'] ?? ''; ?>
                <div class="space-y-3">
                    <label class="flex items-start p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg <?php echo $canEditSec345 ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700' : 'cursor-not-allowed opacity-80'; ?> transition border border-gray-200 dark:border-gray-600">
                        <input type="radio" name="result_decision" value="certified" <?php echo $currentDec === 'certified' ? 'checked' : ''; ?> <?php echo !$canEditSec345 ? 'disabled' : ''; ?> class="mt-1 w-4 h-4 text-emerald-600 focus:ring-emerald-500">
                        <div class="ms-3">
                            <span class="font-bold text-emerald-700 dark:text-emerald-400 block text-sm">รับรอง โดยไม่มีการแก้ไข</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">งานวิจัยผ่านทุกรายการตามเกณฑ์ที่กำหนด</span>
                        </div>
                    </label>

                    <label class="flex items-start p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg <?php echo $canEditSec345 ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700' : 'cursor-not-allowed opacity-80'; ?> transition border border-gray-200 dark:border-gray-600">
                        <input type="radio" name="result_decision" value="certified_with_condition" <?php echo $currentDec === 'certified_with_condition' ? 'checked' : ''; ?> <?php echo !$canEditSec345 ? 'disabled' : ''; ?> class="mt-1 w-4 h-4 text-amber-600 focus:ring-amber-500">
                        <div class="ms-3 flex-1">
                            <span class="font-bold text-amber-700 dark:text-amber-400 block text-sm">รับรอง โดยต้องแก้ไขก่อน</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">ให้แก้ไขตามมติคณะกรรมการ และส่งคืนภายในกำหนด:</span>
                            <div class="mt-2 w-48">
                                <input type="date" name="deadline_date" value="<?php echo htmlspecialchars($eval['deadline_date'] ?? ''); ?>" <?php echo !$canEditSec345 ? 'disabled' : ''; ?> class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-xs rounded p-1.5 w-full <?php echo !$canEditSec345 ? 'cursor-not-allowed opacity-80' : ''; ?>">
                            </div>
                        </div>
                    </label>

                    <label class="flex items-start p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg <?php echo $canEditSec345 ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700' : 'cursor-not-allowed opacity-80'; ?> transition border border-gray-200 dark:border-gray-600">
                        <input type="radio" name="result_decision" value="rejected" <?php echo $currentDec === 'rejected' ? 'checked' : ''; ?> <?php echo !$canEditSec345 ? 'disabled' : ''; ?> class="mt-1 w-4 h-4 text-rose-600 focus:ring-rose-500">
                        <div class="ms-3 flex-1">
                            <span class="font-bold text-rose-700 dark:text-rose-400 block text-sm">ไม่รับรอง</span>
                            <div class="mt-2">
                                <input type="text" name="rejection_reason" value="<?php echo htmlspecialchars($eval['rejection_reason'] ?? ''); ?>" placeholder="ระบุเหตุผลที่ไม่รับรอง..." <?php echo !$canEditSec345 ? 'disabled' : ''; ?> class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-xs rounded p-2 w-full <?php echo !$canEditSec345 ? 'cursor-not-allowed opacity-80' : ''; ?>">
                            </div>
                        </div>
                    </label>
                </div>

                <div class="mt-5 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 flex items-center gap-2 text-xs text-blue-800 dark:text-blue-300">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><strong>ระบบเชื่อมต่อสถานะหลักอัตโนมัติ:</strong> เมื่อบันทึกผลการตรวจ สถานะของคำขอจะอัปเดตตรงตามมติทันที (รับรอง ➔ 5. อนุมัติรับรอง, ต้องแก้ไข ➔ 3. นักวิจัยแก้ไขรายงานวิจัย, ไม่รับรอง ➔ 4. ไม่รับรอง)</span>
                </div>
            </div>

            <!-- Submit Button Bar -->
            <?php if ($canEditSec2 || $canEditSec345): ?>
            <div class="sticky bottom-4 bg-white/90 dark:bg-gray-800/90 backdrop-blur-md p-4 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="text-xs text-gray-500">
                    <?php if ($eval && !empty($eval['updated_at'])): ?>
                        บันทึกล่าสุด: <?php echo htmlspecialchars($eval['updated_at']); ?>
                    <?php else: ?>
                        ยังไม่มีการบันทึกผล
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-6 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow focus:ring-4 focus:ring-blue-300 transition inline-flex items-center">
                        <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        <?php 
                        if ($canEditSec2 && $canEditSec345) {
                            echo "บันทึกผลการตรวจรับรอง (ทุกส่วน)";
                        } elseif ($canEditSec2) {
                            echo "บันทึกผลการตรวจเอกสาร (ส่วนที่ ๒)";
                        } else {
                            echo "บันทึกผลการประเมิน (ส่วนที่ ๓, ๔, ๕)";
                        }
                        ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>

        </form>

<?php include 'includes/footer.php'; ?>
