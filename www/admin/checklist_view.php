<?php
/**
 * Admin Research Evaluation / Checklist V2 (Single Request View & Edit)
 */
$pageTitle = "แบบตรวจรับรองงานวิจัย (Checklist V2)";
require_once '../includes/config.php';

// Access Control
check_role(['super_admin', 'data_admin']);

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
        $evalDate = sanitize($_POST['eval_date'] ?? date('Y-m-d'));
        $evaluatorName = sanitize($_POST['evaluator_name'] ?? '');
        $directorName = sanitize($_POST['director_name'] ?? '');

        // หมวด ก
        $docReport = sanitize($_POST['doc_report'] ?? 'pass');
        $docPlagiarism = sanitize($_POST['doc_plagiarism'] ?? 'pass');
        $docCert = sanitize($_POST['doc_cert'] ?? 'pass');
        $docParticipation = sanitize($_POST['doc_participation'] ?? 'pass');
        $docEthics = sanitize($_POST['doc_ethics'] ?? 'pass');

        // หมวด คุณภาพ
        $qConsistent = sanitize($_POST['q_consistent'] ?? 'pass');
        $qLiterature = sanitize($_POST['q_literature'] ?? 'pass');
        $qMethodology = sanitize($_POST['q_methodology'] ?? 'pass');
        $qToolValidity = sanitize($_POST['q_tool_validity'] ?? 'pass');
        $qKnowledge = sanitize($_POST['q_knowledge'] ?? 'pass');

        // หมวด AI
        $aiAcceptableUse = sanitize($_POST['ai_acceptable_use'] ?? 'pass');
        $aiVerifiedCitations = sanitize($_POST['ai_verified_citations'] ?? 'pass');
        $aiResearcherAnalysis = sanitize($_POST['ai_researcher_analysis'] ?? 'pass');

        // ข้อสังเกต
        $issue1 = sanitize($_POST['issue_1'] ?? '');
        $issue2 = sanitize($_POST['issue_2'] ?? '');
        $issue3 = sanitize($_POST['issue_3'] ?? '');
        $issue4 = sanitize($_POST['issue_4'] ?? '');
        $additionalComments = sanitize($_POST['additional_comments'] ?? '');

        // ผลพิจารณา
        $resultDecision = sanitize($_POST['result_decision'] ?? 'certified');
        $deadlineDate = !empty($_POST['deadline_date']) ? sanitize($_POST['deadline_date']) : null;
        $rejectionReason = sanitize($_POST['rejection_reason'] ?? '');

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

        // Optional: Sync status with requests table if requested
        if (!empty($_POST['sync_request_status'])) {
            $mappedStatus = match($resultDecision) {
                'certified' => 'approved',
                'certified_with_condition' => 'correction',
                'rejected' => 'rejected',
                default => null
            };
            if ($mappedStatus) {
                $notes = $additionalComments ?: ($rejectionReason ?: "อัปเดตตามแบบตรวจรับรองงานวิจัย V2");
                $syncStmt = $pdo->prepare("UPDATE requests SET status = ?, correction_note = ? WHERE id = ?");
                $syncStmt->execute([$mappedStatus, $notes, $id]);
            }
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
function renderCheckRadio($name, $currentVal) {
    $currentVal = $currentVal ?: 'pass';
    $options = [
        'pass' => ['label' => '✓ ผ่าน', 'class' => 'text-emerald-600 bg-emerald-50 border-emerald-300 dark:bg-emerald-950 dark:border-emerald-800'],
        'fail' => ['label' => '✗ ไม่ผ่าน', 'class' => 'text-rose-600 bg-rose-50 border-rose-300 dark:bg-rose-950 dark:border-rose-800'],
        'edit' => ['label' => 'ให้แก้ไข', 'class' => 'text-amber-600 bg-amber-50 border-amber-300 dark:bg-amber-950 dark:border-amber-800'],
    ];

    $html = '<div class="inline-flex space-x-2 text-xs">';
    foreach ($options as $val => $opt) {
        $checked = ($currentVal === $val) ? 'checked' : '';
        $html .= '<label class="flex items-center px-2.5 py-1.5 rounded border cursor-pointer hover:opacity-80 transition ' . $opt['class'] . '">';
        $html .= '<input type="radio" name="' . htmlspecialchars($name) . '" value="' . $val . '" ' . $checked . ' class="w-3.5 h-3.5 text-blue-600 focus:ring-blue-500">';
        $html .= '<span class="ms-1.5 font-medium whitespace-nowrap">' . $opt['label'] . '</span>';
        $html .= '</label>';
    }
    $html .= '</div>';
    return $html;
}

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
                    <span>📋 แบบตรวจรับรองงานวิจัยเพื่อขอตำแหน่งทางวิชาการ (Checklist V2)</span>
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
                <button type="button" onclick="window.print()" class="px-3.5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 inline-flex items-center shadow-sm">
                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    พิมพ์เอกสาร / PDF
                </button>
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
                <h2 class="text-base font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-2 mb-4 flex items-center">
                    <span class="w-2.5 h-2.5 bg-blue-600 rounded-full me-2"></span>
                    ส่วนที่ ๑ ข้อมูลทั่วไป
                </h2>
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
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-2 mb-4">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center">
                        <span class="w-2.5 h-2.5 bg-amber-500 rounded-full me-2"></span>
                        ส่วนที่ ๒ รายการตรวจสอบ: หมวด ก — ความครบถ้วนของเอกสาร (สำหรับเจ้าหน้าที่กลั่นกรอง)
                    </h2>
                    <span class="text-xs text-gray-400">๕ รายการหลัก</span>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="text-sm">
                            <span class="font-medium text-gray-900 dark:text-white">๑. เล่มรายงานวิจัยฉบับสมบูรณ์</span>
                            <?php if (!empty($request['file_report'])): ?>
                                <a href="../<?php echo htmlspecialchars($request['file_report']); ?>" target="_blank" class="ms-2 text-xs text-blue-600 hover:underline inline-flex items-center">🔗 เปิดดูไฟล์</a>
                            <?php else: ?><span class="ms-2 text-xs text-red-500">(ยังไม่มีไฟล์)</span><?php endif; ?>
                        </div>
                        <?php echo renderCheckRadio('doc_report', $eval['doc_report'] ?? 'pass'); ?>
                    </div>

                    <div class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="text-sm">
                            <span class="font-medium text-gray-900 dark:text-white">๒. ผลตรวจอักขราวิสุทธิ์</span>
                            <?php if ($request['plagiarism_percent'] !== null): ?>
                                <span class="ms-2 text-xs px-2 py-0.5 bg-gray-100 text-gray-700 rounded dark:bg-gray-700 dark:text-gray-300 font-bold"><?php echo htmlspecialchars($request['plagiarism_percent']); ?>%</span>
                            <?php endif; ?>
                            <?php if (!empty($request['file_plagiarism'])): ?>
                                <a href="../<?php echo htmlspecialchars($request['file_plagiarism']); ?>" target="_blank" class="ms-2 text-xs text-blue-600 hover:underline inline-flex items-center">🔗 เปิดดูไฟล์</a>
                            <?php endif; ?>
                        </div>
                        <?php echo renderCheckRadio('doc_plagiarism', $eval['doc_plagiarism'] ?? 'pass'); ?>
                    </div>

                    <div class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="text-sm">
                            <span class="font-medium text-gray-900 dark:text-white">๓. บทความวิจัย / แบบตอบรับการตีพิมพ์ (วารสารอยู่ในฐานข้อมูลที่สถาบันยอมรับ)</span>
                            <?php if (!empty($request['file_cert'])): ?>
                                <a href="../<?php echo htmlspecialchars($request['file_cert']); ?>" target="_blank" class="ms-2 text-xs text-blue-600 hover:underline inline-flex items-center">🔗 เปิดดูไฟล์</a>
                            <?php endif; ?>
                        </div>
                        <?php echo renderCheckRadio('doc_cert', $eval['doc_cert'] ?? 'pass'); ?>
                    </div>

                    <div class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="text-sm">
                            <span class="font-medium text-gray-900 dark:text-white">๔. เอกสารสัดส่วนการมีส่วนร่วม (ลงนามครบทุกคน)</span>
                            <?php if (!empty($request['file_participation'])): ?>
                                <a href="../<?php echo htmlspecialchars($request['file_participation']); ?>" target="_blank" class="ms-2 text-xs text-blue-600 hover:underline inline-flex items-center">🔗 เปิดดูไฟล์</a>
                            <?php endif; ?>
                        </div>
                        <?php echo renderCheckRadio('doc_participation', $eval['doc_participation'] ?? 'pass'); ?>
                    </div>

                    <div class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="text-sm">
                            <span class="font-medium text-gray-900 dark:text-white">๕. เอกสารรับรองจริยธรรมการวิจัยในมนุษย์</span>
                            <?php if (!empty($request['file_ethics'])): ?>
                                <a href="../<?php echo htmlspecialchars($request['file_ethics']); ?>" target="_blank" class="ms-2 text-xs text-blue-600 hover:underline inline-flex items-center">🔗 เปิดดูไฟล์</a>
                            <?php endif; ?>
                        </div>
                        <?php echo renderCheckRadio('doc_ethics', $eval['doc_ethics'] ?? 'pass'); ?>
                    </div>
                </div>
            </div>

            <!-- ส่วนที่ ๓: คุณภาพงานวิจัย & การใช้ AI -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6">
                <div class="border-b border-gray-100 dark:border-gray-700 pb-2 mb-4">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center">
                        <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full me-2"></span>
                        ส่วนที่ ๓ การรับรองคุณภาพงานวิจัยและการใช้ปัญญาประดิษฐ์ (สำหรับคณะกรรมการ)
                    </h2>
                </div>

                <!-- คุณภาพงานวิจัย -->
                <div class="mb-5">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                        📌 ด้านคุณภาพงานวิจัย
                    </h3>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div class="py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <span class="text-sm text-gray-800 dark:text-gray-200">๑. ชื่อเรื่อง บทคัดย่อ วัตถุประสงค์ และข้อสรุปสอดคล้องกัน</span>
                            <?php echo renderCheckRadio('q_consistent', $eval['q_consistent'] ?? 'pass'); ?>
                        </div>
                        <div class="py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <span class="text-sm text-gray-800 dark:text-gray-200">๒. การทบทวนวรรณกรรม ครอบคลุมและทันสมัย อ้างอิงแหล่งที่มาที่มีอยู่จริง</span>
                            <?php echo renderCheckRadio('q_literature', $eval['q_literature'] ?? 'pass'); ?>
                        </div>
                        <div class="py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <span class="text-sm text-gray-800 dark:text-gray-200">๓. ระเบียบวิธีวิจัยเหมาะสมกับวัตถุประสงค์</span>
                            <?php echo renderCheckRadio('q_methodology', $eval['q_methodology'] ?? 'pass'); ?>
                        </div>
                        <div class="py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <span class="text-sm text-gray-800 dark:text-gray-200">๔. เครื่องมือวิจัยผ่านการตรวจสอบความเที่ยงตรง / ความเชื่อมั่น</span>
                            <?php echo renderCheckRadio('q_tool_validity', $eval['q_tool_validity'] ?? 'pass'); ?>
                        </div>
                        <div class="py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <span class="text-sm text-gray-800 dark:text-gray-200">๕. ผลการวิจัยและข้อสรุปมีองค์ความรู้ที่เกิดจากงานวิจัย</span>
                            <?php echo renderCheckRadio('q_knowledge', $eval['q_knowledge'] ?? 'pass'); ?>
                        </div>
                    </div>
                </div>

                <!-- การใช้ปัญญาประดิษฐ์ AI -->
                <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center text-purple-700 dark:text-purple-400">
                        🤖 ด้านการใช้ปัญญาประดิษฐ์ (AI) ในงานวิจัย
                    </h3>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div class="py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <span class="text-sm text-gray-800 dark:text-gray-200">๑. AI ถูกใช้เฉพาะในระดับที่ยอมรับได้ (ตรวจภาษา/สืบค้น/ร่างเบื้องต้น) ไม่ใช้ AI เขียนข้อมูลหลักหรือสรุปผลแทนผู้วิจัย</span>
                            <?php echo renderCheckRadio('ai_acceptable_use', $eval['ai_acceptable_use'] ?? 'pass'); ?>
                        </div>
                        <div class="py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <span class="text-sm text-gray-800 dark:text-gray-200">๒. ข้อมูลและข้อเท็จจริงทุกส่วนได้รับการตรวจสอบโดยผู้วิจัยด้วยตนเอง รวมถึงการอ้างอิง — ไม่มีแหล่งอ้างอิงที่ไม่มีอยู่จริง (Hallucinated Citations)</span>
                            <?php echo renderCheckRadio('ai_verified_citations', $eval['ai_verified_citations'] ?? 'pass'); ?>
                        </div>
                        <div class="py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <span class="text-sm text-gray-800 dark:text-gray-200">๓. บทวิเคราะห์ ตีความ และสรุปผลเป็นของผู้วิจัย ไม่ใช่ผลลัพธ์จาก AI โดยตรง</span>
                            <?php echo renderCheckRadio('ai_researcher_analysis', $eval['ai_researcher_analysis'] ?? 'pass'); ?>
                        </div>
                    </div>
                    <div class="mt-3 p-3 bg-red-50 dark:bg-red-950/40 rounded-lg text-xs text-red-700 dark:text-red-300 flex items-start">
                        <svg class="w-4 h-4 me-1.5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                        <span><strong>หมายเหตุ:</strong> หากพบว่าผู้ขอรับรองใช้ปัญญาประดิษฐ์ในงานวิจัยมากและแหล่งที่มาผิดมากซึ่งเสี่ยงต่อจริยธรรมการวิจัย ให้ถือ <u>ไม่รับรอง</u></span>
                    </div>
                </div>
            </div>

            <!-- ส่วนข้อสังเกตและรายการที่ต้องแก้ไข -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6">
                <h2 class="text-base font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-2 mb-4 flex items-center">
                    <span class="w-2.5 h-2.5 bg-rose-500 rounded-full me-2"></span>
                    ข้อสังเกตและรายการที่ต้องแก้ไข
                </h2>
                <div class="space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">๑. รายการที่ต้องแก้ไข ข้อ ๑:</label>
                            <input type="text" name="issue_1" value="<?php echo htmlspecialchars($eval['issue_1'] ?? ''); ?>" placeholder="ระบุรายละเอียด..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">๒. รายการที่ต้องแก้ไข ข้อ ๒:</label>
                            <input type="text" name="issue_2" value="<?php echo htmlspecialchars($eval['issue_2'] ?? ''); ?>" placeholder="ระบุรายละเอียด..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">๓. รายการที่ต้องแก้ไข ข้อ ๓:</label>
                            <input type="text" name="issue_3" value="<?php echo htmlspecialchars($eval['issue_3'] ?? ''); ?>" placeholder="ระบุรายละเอียด..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">๔. รายการที่ต้องแก้ไข ข้อ ๔:</label>
                            <input type="text" name="issue_4" value="<?php echo htmlspecialchars($eval['issue_4'] ?? ''); ?>" placeholder="ระบุรายละเอียด..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">ความเห็นเพิ่มเติม:</label>
                        <textarea name="additional_comments" rows="3" placeholder="ระบุความเห็นเพิ่มเติมของคณะกรรมการ..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"><?php echo htmlspecialchars($eval['additional_comments'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- ส่วนที่ ๔: ผลการพิจารณา -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6">
                <h2 class="text-base font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-2 mb-4 flex items-center">
                    <span class="w-2.5 h-2.5 bg-blue-600 rounded-full me-2"></span>
                    ส่วนที่ ๔ ผลการพิจารณา
                </h2>
                <?php $currentDec = $eval['result_decision'] ?? 'certified'; ?>
                <div class="space-y-3">
                    <label class="flex items-start p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition border border-gray-200 dark:border-gray-600">
                        <input type="radio" name="result_decision" value="certified" <?php echo $currentDec === 'certified' ? 'checked' : ''; ?> class="mt-1 w-4 h-4 text-emerald-600 focus:ring-emerald-500">
                        <div class="ms-3">
                            <span class="font-bold text-emerald-700 dark:text-emerald-400 block text-sm">รับรอง โดยไม่มีการแก้ไข</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">งานวิจัยผ่านทุกรายการตามเกณฑ์ที่กำหนด</span>
                        </div>
                    </label>

                    <label class="flex items-start p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition border border-gray-200 dark:border-gray-600">
                        <input type="radio" name="result_decision" value="certified_with_condition" <?php echo $currentDec === 'certified_with_condition' ? 'checked' : ''; ?> class="mt-1 w-4 h-4 text-amber-600 focus:ring-amber-500">
                        <div class="ms-3 flex-1">
                            <span class="font-bold text-amber-700 dark:text-amber-400 block text-sm">รับรอง โดยต้องแก้ไขก่อน</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">ให้แก้ไขตามมติคณะกรรมการ และส่งคืนภายในกำหนด:</span>
                            <div class="mt-2 w-48">
                                <input type="date" name="deadline_date" value="<?php echo htmlspecialchars($eval['deadline_date'] ?? ''); ?>" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-xs rounded p-1.5 w-full">
                            </div>
                        </div>
                    </label>

                    <label class="flex items-start p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition border border-gray-200 dark:border-gray-600">
                        <input type="radio" name="result_decision" value="rejected" <?php echo $currentDec === 'rejected' ? 'checked' : ''; ?> class="mt-1 w-4 h-4 text-rose-600 focus:ring-rose-500">
                        <div class="ms-3 flex-1">
                            <span class="font-bold text-rose-700 dark:text-rose-400 block text-sm">ไม่รับรอง</span>
                            <div class="mt-2">
                                <input type="text" name="rejection_reason" value="<?php echo htmlspecialchars($eval['rejection_reason'] ?? ''); ?>" placeholder="ระบุเหตุผลที่ไม่รับรอง..." class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-xs rounded p-2 w-full">
                            </div>
                        </div>
                    </label>
                </div>

                <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center">
                    <input type="checkbox" id="sync_request_status" name="sync_request_status" value="1" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                    <label for="sync_request_status" class="ms-2 text-xs font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                        🔄 อัปเดตสถานะของคำขอในระบบให้อัตโนมัติ (เช่น ผ่าน -> อนุมัติ / แก้ไข -> แจ้งแก้ไข)
                    </label>
                </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="sticky bottom-4 bg-white/90 dark:bg-gray-800/90 backdrop-blur-md p-4 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="text-xs text-gray-500">
                    <?php if ($eval && !empty($eval['updated_at'])): ?>
                        บันทึกล่าสุด: <?php echo htmlspecialchars($eval['updated_at']); ?>
                    <?php else: ?>
                        ยังไม่มีการบันทึกผล
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-6 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow focus:ring-4 focus:ring-blue-300 transition">
                        💾 บันทึกผลการตรวจรับรอง
                    </button>
                </div>
            </div>

        </form>

<?php include 'includes/footer.php'; ?>
