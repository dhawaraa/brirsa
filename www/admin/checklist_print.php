<?php
/**
 * Print Template for Research Checklist V2 (Exact Match to Word Document checklist_research_cert_v2.docx)
 */
require_once '../includes/security.php';
secure_session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

require_once '../includes/config.php';

check_role(['super_admin', 'data_admin', 'committee']);

$id = intval($_GET['id'] ?? 0);
$pdo = getDB();

// Fetch request
$stmt = $pdo->prepare("SELECT * FROM requests WHERE id = ?");
$stmt->execute([$id]);
$request = $stmt->fetch();

if (!$request) {
    die("ไม่พบข้อมูลคำขอ");
}

// Fetch existing evaluation
$stmtEval = $pdo->prepare("SELECT * FROM request_evaluations WHERE request_id = ? ORDER BY id DESC LIMIT 1");
$stmtEval->execute([$id]);
$eval = $stmtEval->fetch();

// Format Thai Date
function formatThaiDateLong($dateStr) {
    if (!$dateStr || $dateStr === '0000-00-00') return '......... / ......... / .............';
    $timestamp = strtotime($dateStr);
    if (!$timestamp) return '......... / ......... / .............';
    $d = date('j', $timestamp);
    $thai_months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    $m = $thai_months[intval(date('n', $timestamp))];
    $y = date('Y', $timestamp) + 543;
    return "$d $m $y";
}

function formatThaiDateShort($dateStr) {
    if (!$dateStr || $dateStr === '0000-00-00') return '...../...../..........';
    $timestamp = strtotime($dateStr);
    if (!$timestamp) return '...../...../..........';
    $d = date('d', $timestamp);
    $m = date('m', $timestamp);
    $y = date('Y', $timestamp) + 543;
    return "$d/$m/$y";
}

// Helper to render check state like docx: ✓ in box or checked box
function renderBox($isChecked) {
    if ($isChecked) {
        return '<span class="box-checked">✓</span>';
    }
    return '<span class="box-empty"></span>';
}

function checkVal($current, $target) {
    if (empty($current)) return false;
    return $current === $target;
}

$acadPos = $request['academic_position'] ?? '';
$isAsstProf = (strpos($acadPos, 'ผศ') !== false);
$isAssocProf = (strpos($acadPos, 'รศ') !== false);
$isProf = (strpos($acadPos, 'ศ') !== false && !$isAsstProf && !$isAssocProf);

$evalDateStr = formatThaiDateShort($eval['eval_date'] ?? $request['created_at']);
$evalDateLong = formatThaiDateLong($eval['eval_date'] ?? $request['created_at']);
$evaluator = $eval['evaluator_name'] ?? ($_SESSION['admin_name'] ?? '');
$director = $eval['director_name'] ?? 'ผู้อำนวยการสถาบันวิจัยพุทธศาสตร์';
$deadlineStr = formatThaiDateShort($eval['deadline_date'] ?? '');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แบบตรวจรับรองงานวิจัย - <?php echo htmlspecialchars($request['request_id']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #cbd5e1;
            font-family: 'TH SarabunPSK', 'TH Sarabun New', 'Sarabun', sans-serif;
            font-size: 14pt;
            line-height: 1.2;
            color: #000;
        }

        .no-print {
            background: #1e293b;
            color: #fff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .btn {
            font-family: 'Sarabun', sans-serif;
            font-size: 14px;
            font-weight: 500;
            padding: 8px 18px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid transparent;
        }

        .btn-primary {
            background-color: #2563eb;
            color: #fff;
        }
        .btn-primary:hover { background-color: #1d4ed8; }

        .btn-secondary {
            background-color: #334155;
            color: #f8fafc;
            border-color: #475569;
        }
        .btn-secondary:hover { background-color: #475569; }

        /* A4 Sheets layout matching Word natural flow */
        .a4-sheet {
            width: 210mm;
            min-height: 297mm;
            padding: 18mm 20mm;
            margin: 20px auto;
            background: #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            position: relative;
            box-sizing: border-box;
        }

        @media print {
            html, body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .no-print { display: none !important; }
            .a4-sheet {
                margin: 0 !important;
                width: 100% !important;
                min-height: auto !important;
                padding: 16mm 18mm !important;
                box-shadow: none !important;
                border: none !important;
                page-break-after: always !important;
                break-after: page !important;
            }
            .a4-sheet:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
        }

        /* Typography matching docx */
        h1.doc-title {
            font-size: 16.5pt;
            font-weight: bold;
            text-align: center;
            margin: 0 0 2px 0;
            line-height: 1.2;
        }

        h2.doc-subtitle {
            font-size: 15.5pt;
            font-weight: normal;
            text-align: center;
            margin: 0 0 8px 0;
            line-height: 1.2;
        }

        .section-header-badge {
            font-size: 14.5pt;
            font-weight: bold;
            margin-top: 4px;
            margin-bottom: 2px;
        }

        .section-title {
            font-size: 14.5pt;
            font-weight: bold;
            margin-top: 4px;
            margin-bottom: 4px;
        }

        /* Tables matching docx */
        table.form-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 14pt;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        table.form-table, table.form-table th, table.form-table td {
            border: 1px solid #000;
        }

        table.form-table th, table.form-table td {
            padding: 5px 8px;
            vertical-align: top;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        table.form-table th {
            text-align: center;
            font-weight: bold;
            background-color: #f8fafc;
        }

        .col-center {
            text-align: center;
            vertical-align: middle !important;
        }

        /* Signature block table */
        table.sign-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin-top: 14px;
            margin-bottom: 0;
            font-size: 14pt;
        }

        table.sign-table, table.sign-table td {
            border: 1px solid #000;
        }

        table.sign-table td {
            width: 50%;
            padding: 10px 14px;
            vertical-align: top;
            text-align: center;
            line-height: 1.5;
        }

        /* Checkbox styling */
        .box-empty {
            display: inline-block;
            width: 15px;
            height: 15px;
            border: 1px solid #000;
            vertical-align: middle;
            margin-right: 2px;
            margin-bottom: 2px;
        }

        .box-checked {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 15px;
            height: 15px;
            border: 1px solid #000;
            vertical-align: middle;
            font-size: 12pt;
            font-weight: bold;
            line-height: 1;
            margin-right: 2px;
            margin-bottom: 2px;
        }

        .fill-text {
            font-weight: bold;
            color: #000;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .dotted-line {
            display: inline-block;
            border-bottom: 1px dotted #000;
            min-height: 18px;
        }

        .note-alert {
            font-size: 13.5pt;
            font-weight: normal;
            font-style: italic;
            padding: 4px 6px;
            background-color: #fff;
        }
    </style>
</head>
<body>

    <!-- Print Topbar -->
    <div class="no-print">
        <div style="font-family: 'Sarabun', sans-serif;">
            <span style="font-weight: 700; color: #1e293b;">แบบตรวจรับรองงานวิจัย</span>
            <span style="color: #64748b; font-size: 13px; margin-left: 8px;">รหัสคำขอ: <?php echo htmlspecialchars($request['request_id']); ?></span>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="checklist_view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                ← กลับหน้าแก้ไขแบบตรวจ
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ พิมพ์เอกสาร / บันทึกเป็น PDF
            </button>
        </div>
    </div>

    <!-- ==================== หน้าที่ ๑ (สำหรับเจ้าหน้าที่ผู้ตรวจสอบ กลั่นกรองข้อมูล) ==================== -->
    <div class="a4-sheet">
        <div>
            <h1 class="doc-title">แบบตรวจรับรองงานวิจัยเพื่อขอตำแหน่งทางวิชาการ</h1>
            <h2 class="doc-subtitle">สถาบันวิจัยพุทธศาสตร์ มหาวิทยาลัยมหาจุฬาลงกรณราชวิทยาลัย</h2>

            <div class="section-header-badge">สำหรับเจ้าหน้าที่ผู้ตรวจสอบ กลั่นกรองข้อมูล</div>
            <div class="section-title">ส่วนที่ ๑ ข้อมูลทั่วไป</div>

            <!-- Table 1: ข้อมูลทั่วไป -->
            <table class="form-table">
                <tr>
                    <td style="width: 55%;">
                        <strong>ชื่อ-สกุล ผู้ขอรับรอง:</strong>
                        <span class="fill-text"><?php echo htmlspecialchars($request['name']); ?></span>
                    </td>
                    <td style="width: 45%;">
                        <strong>ตำแหน่งที่ขอ:</strong> &nbsp;
                        <?php echo renderBox($isAsstProf); ?> ผศ. &nbsp;&nbsp;
                        <?php echo renderBox($isAssocProf); ?> รศ. &nbsp;&nbsp;
                        <?php echo renderBox($isProf); ?> ศ.
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <strong>ชื่องานวิจัย:</strong>
                        <span class="fill-text"><?php echo htmlspecialchars($request['project_title']); ?></span>
                    </td>
                </tr>
                <tr>
                    <td style="width: 55%;">
                        <strong>สาขาวิชา / คณะ:</strong>
                        <span class="fill-text"><?php echo htmlspecialchars(($request['branch'] ? $request['branch'] . ' / ' : '') . $request['affiliation']); ?></span>
                    </td>
                    <td style="width: 45%;">
                        <strong>วันที่รับเอกสาร:</strong>
                        <span class="fill-text"><?php echo $evalDateStr; ?></span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <strong>ผู้ตรวจรับรอง:</strong>
                        <span class="fill-text"><?php echo htmlspecialchars($evaluator ?: '-'); ?></span>
                    </td>
                </tr>
            </table>

            <div class="section-title">ส่วนที่ ๒ รายการตรวจสอบ (Checklist)</div>

            <!-- Table 2: หมวด ก — ความครบถ้วนของเอกสาร -->
            <table class="form-table">
                <thead>
                    <tr>
                        <th style="width: 61%; text-align: left; padding-left: 8px;">รายการ</th>
                        <th style="width: 13%;">✓ ผ่าน</th>
                        <th style="width: 13%;">✗ ไม่ผ่าน</th>
                        <th style="width: 13%;">ให้แก้ไข</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background-color: #f8fafc;">
                        <td colspan="4" style="font-weight: bold;">หมวด ก — ความครบถ้วนของเอกสาร</td>
                    </tr>
                    <tr>
                        <td>๑. เล่มรายงานวิจัยฉบับสมบูรณ์</td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['doc_report'] ?? '', 'pass')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['doc_report'] ?? '', 'fail')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['doc_report'] ?? '', 'edit')); ?></td>
                    </tr>
                    <tr>
                        <td>
                            ๒. ผลตรวจอักขราวิสุทธิ์ 
                            <?php if ($request['plagiarism_percent'] !== null): ?>
                                <span style="font-size: 11pt; color: #475569;">(<?php echo htmlspecialchars($request['plagiarism_percent']); ?>%)</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['doc_plagiarism'] ?? '', 'pass')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['doc_plagiarism'] ?? '', 'fail')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['doc_plagiarism'] ?? '', 'edit')); ?></td>
                    </tr>
                    <tr>
                        <td>๓. บทความวิจัย / แบบตอบรับการตีพิมพ์ วารสารอยู่ในฐานข้อมูลที่สถาบันยอมรับ</td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['doc_cert'] ?? '', 'pass')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['doc_cert'] ?? '', 'fail')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['doc_cert'] ?? '', 'edit')); ?></td>
                    </tr>
                    <tr>
                        <td>๔. เอกสารสัดส่วนการมีส่วนร่วม (ลงนามครบทุกคน)</td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['doc_participation'] ?? '', 'pass')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['doc_participation'] ?? '', 'fail')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['doc_participation'] ?? '', 'edit')); ?></td>
                    </tr>
                    <tr>
                        <td>๕. เอกสารรับรองจริยธรรมการวิจัยในมนุษย์</td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['doc_ethics'] ?? '', 'pass')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['doc_ethics'] ?? '', 'fail')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['doc_ethics'] ?? '', 'edit')); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Table 3: ลงชื่อ สำหรับเจ้าหน้าที่ -->
        <table class="sign-table">
            <tr>
                <td>
                    <div style="min-height: 48px;"></div>
                    ลงชื่อ ................................................................<br>
                    ( <?php echo htmlspecialchars($evaluator ?: '................................................................'); ?> )<br>
                    <strong>ผู้ตรวจกลั่นกรอง</strong><br>
                    วันที่ <?php echo $evalDateLong; ?>
                </td>
                <td>
                    <div style="min-height: 48px;"></div>
                    ลงชื่อ ................................................................<br>
                    ( <?php echo htmlspecialchars($director ?: '................................................................'); ?> )<br>
                    <strong>ผู้อำนวยการ</strong><br>
                    วันที่ <?php echo $evalDateLong; ?>
                </td>
            </tr>
        </table>
    </div>


    <!-- ==================== หน้าที่ ๒ (สำหรับคณะกรรมการตรวจสอบรับรอง) ==================== -->
    <div class="a4-sheet">
        <div>
            <div class="section-header-badge">สำหรับคณะกรรมการตรวจสอบรับรอง</div>
            <div class="section-title">ส่วนที่ ๓ การรับรอง</div>

            <!-- Table 4: คุณภาพงานวิจัย & การใช้ AI -->
            <table class="form-table">
                <thead>
                    <tr>
                        <th style="width: 61%; text-align: left; padding-left: 8px;">รายการ</th>
                        <th style="width: 13%;">✓ ผ่าน</th>
                        <th style="width: 13%;">✗ ไม่ผ่าน</th>
                        <th style="width: 13%;">ให้แก้ไข</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background-color: #f8fafc;">
                        <td colspan="4" style="font-weight: bold;">คุณภาพงานวิจัย</td>
                    </tr>
                    <tr>
                        <td>๑. ชื่อเรื่อง บทคัดย่อ วัตถุประสงค์ และข้อสรุปสอดคล้องกัน</td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['q_consistent'] ?? '', 'pass')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['q_consistent'] ?? '', 'fail')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['q_consistent'] ?? '', 'edit')); ?></td>
                    </tr>
                    <tr>
                        <td>๒. การทบทวนวรรณกรรม ครอบคลุมและทันสมัย อ้างอิงแหล่งที่มาที่มีอยู่จริง</td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['q_literature'] ?? '', 'pass')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['q_literature'] ?? '', 'fail')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['q_literature'] ?? '', 'edit')); ?></td>
                    </tr>
                    <tr>
                        <td>๓. ระเบียบวิธีวิจัยเหมาะสมกับวัตถุประสงค์</td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['q_methodology'] ?? '', 'pass')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['q_methodology'] ?? '', 'fail')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['q_methodology'] ?? '', 'edit')); ?></td>
                    </tr>
                    <tr>
                        <td>๔. เครื่องมือวิจัยผ่านการตรวจสอบความเที่ยงตรง / ความเชื่อมั่น</td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['q_tool_validity'] ?? '', 'pass')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['q_tool_validity'] ?? '', 'fail')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['q_tool_validity'] ?? '', 'edit')); ?></td>
                    </tr>
                    <tr>
                        <td>๕. ผลการวิจัยและข้อสรุปมีองค์ความรู้ที่เกิดจากงานวิจัย</td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['q_knowledge'] ?? '', 'pass')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['q_knowledge'] ?? '', 'fail')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['q_knowledge'] ?? '', 'edit')); ?></td>
                    </tr>

                    <tr style="background-color: #f8fafc;">
                        <td colspan="4" style="font-weight: bold;">การใช้ปัญญาประดิษฐ์ (AI) ในงานวิจัย</td>
                    </tr>
                    <tr>
                        <td>๑. AI ถูกใช้เฉพาะในระดับที่ยอมรับได้ (ตรวจภาษา / สืบค้น / ร่างเบื้องต้น) ไม่ใช้ AI เขียนข้อมูลหลักหรือสรุปผลแทนผู้วิจัย</td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['ai_acceptable_use'] ?? '', 'pass')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['ai_acceptable_use'] ?? '', 'fail')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['ai_acceptable_use'] ?? '', 'edit')); ?></td>
                    </tr>
                    <tr>
                        <td>๒. ข้อมูลและข้อเท็จจริงทุกส่วนได้รับการตรวจสอบโดยผู้วิจัยด้วยตนเอง รวมถึงการอ้างอิง — ไม่มีแหล่งอ้างอิงที่ไม่มีอยู่จริง (Hallucinated Citations)</td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['ai_verified_citations'] ?? '', 'pass')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['ai_verified_citations'] ?? '', 'fail')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['ai_verified_citations'] ?? '', 'edit')); ?></td>
                    </tr>
                    <tr>
                        <td>๓. บทวิเคราะห์ ตีความ และสรุปผลเป็นของผู้วิจัย ไม่ใช่ผลลัพธ์จาก AI โดยตรง</td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['ai_researcher_analysis'] ?? '', 'pass')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['ai_researcher_analysis'] ?? '', 'fail')); ?></td>
                        <td class="col-center"><?php echo renderBox(checkVal($eval['ai_researcher_analysis'] ?? '', 'edit')); ?></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="note-alert">
                            <strong>หมายเหตุ:</strong> หากพบว่า ผู้ขอรับรองใช้ปัญญาประดิษฐ์ในงานวิจัยมากและแหล่งที่มาผิดมากซึ่งเสี่ยงต่อจริยธรรมการวิจัย ให้ถือ <u>ไม่รับรอง</u>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="section-title">ส่วนที่ ๔ ข้อสังเกตและรายการที่ต้องแก้ไข</div>

            <!-- Table 5: ข้อสังเกตและรายการที่ต้องแก้ไข -->
            <table class="form-table">
                <tr>
                    <td style="padding: 6px 10px; line-height: 1.6;">
                        <div style="font-weight: bold; margin-bottom: 2px;">รายการที่ไม่ผ่าน / ต้องแก้ไข (ระบุข้อที่และรายละเอียด)</div>
                        <div style="display: flex; align-items: baseline; margin-bottom: 2px;">
                            <span style="white-space: nowrap; margin-right: 6px;">๑.</span>
                            <div style="flex: 1; border-bottom: 1px dotted #000; min-height: 20px;">
                                <?php if (!empty($eval['issue_1'])): ?>
                                    <span class="fill-text" style="text-decoration: none;"><?php echo htmlspecialchars($eval['issue_1']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="display: flex; align-items: baseline; margin-bottom: 2px;">
                            <span style="white-space: nowrap; margin-right: 6px;">๒.</span>
                            <div style="flex: 1; border-bottom: 1px dotted #000; min-height: 20px;">
                                <?php if (!empty($eval['issue_2'])): ?>
                                    <span class="fill-text" style="text-decoration: none;"><?php echo htmlspecialchars($eval['issue_2']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="display: flex; align-items: baseline; margin-bottom: 2px;">
                            <span style="white-space: nowrap; margin-right: 6px;">๓.</span>
                            <div style="flex: 1; border-bottom: 1px dotted #000; min-height: 20px;">
                                <?php if (!empty($eval['issue_3'])): ?>
                                    <span class="fill-text" style="text-decoration: none;"><?php echo htmlspecialchars($eval['issue_3']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="display: flex; align-items: baseline; margin-bottom: 4px;">
                            <span style="white-space: nowrap; margin-right: 6px;">๔.</span>
                            <div style="flex: 1; border-bottom: 1px dotted #000; min-height: 20px;">
                                <?php if (!empty($eval['issue_4'])): ?>
                                    <span class="fill-text" style="text-decoration: none;"><?php echo htmlspecialchars($eval['issue_4']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="font-weight: bold; margin-top: 6px; margin-bottom: 4px;">ความเห็นเพิ่มเติม</div>
                        <?php if (!empty($eval['additional_comments'])): ?>
                            <div style="border-bottom: 1px dotted #000; min-height: 24px;">
                                <span class="fill-text" style="text-decoration: none;"><?php echo nl2br(htmlspecialchars($eval['additional_comments'])); ?></span>
                            </div>
                        <?php else: ?>
                            <div style="border-bottom: 1px dotted #000; height: 22px; margin-bottom: 6px;"></div>
                            <div style="border-bottom: 1px dotted #000; height: 22px; margin-bottom: 6px;"></div>
                            <div style="border-bottom: 1px dotted #000; height: 22px;"></div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>


    <!-- ==================== หน้าที่ ๓ (ผลการพิจารณาและการลงนาม) ==================== -->
    <div class="a4-sheet">
        <div>
            <div class="section-header-badge">สำหรับคณะกรรมการตรวจสอบรับรอง</div>
            <div class="section-title" style="margin-top: 10px;">ส่วนที่ ๕ ผลการพิจารณา</div>

            <!-- Table 6: ผลการพิจารณา -->
            <?php 
                $dec = $eval['result_decision'] ?? '';
            ?>
            <table class="form-table" style="margin-bottom: 25px;">
                <tr>
                    <td style="padding: 12px 14px; line-height: 1.8;">
                        <div style="margin-bottom: 8px;">
                            <?php echo renderBox($dec === 'certified'); ?> &nbsp;
                            <strong>รับรอง โดยไม่มีการแก้ไข</strong> — งานวิจัยผ่านทุกรายการตามเกณฑ์
                        </div>
                        <div style="display: flex; align-items: baseline; margin-bottom: 8px; flex-wrap: wrap;">
                            <div>
                                <?php echo renderBox($dec === 'certified_with_condition'); ?> &nbsp;
                                <strong>รับรอง โดยต้องแก้ไขก่อน</strong> — ให้แก้ไขตามมติคณะกรรมการ และส่งคืนภายในวันที่: &nbsp;
                            </div>
                            <div style="display: inline-block; border-bottom: 1px dotted #000; min-width: 160px; text-align: center;">
                                <span class="fill-text" style="text-decoration: none;"><?php echo ($dec === 'certified_with_condition' && !empty($eval['deadline_date'])) ? $deadlineStr : ''; ?></span>
                            </div>
                        </div>
                        <div style="display: flex; align-items: baseline; flex-wrap: wrap;">
                            <div style="white-space: nowrap;">
                                <?php echo renderBox($dec === 'rejected'); ?> &nbsp;
                                <strong>ไม่รับรอง</strong> — เนื่องจาก: &nbsp;
                            </div>
                            <div style="flex: 1; min-width: 250px; border-bottom: 1px dotted #000;">
                                <span class="fill-text" style="text-decoration: none;"><?php echo ($dec === 'rejected' && !empty($eval['rejection_reason'])) ? htmlspecialchars($eval['rejection_reason']) : ''; ?></span>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Table 7: ลงชื่อ คณะกรรมการ -->
        <table class="sign-table" style="margin-top: 30px;">
            <tr>
                <td>
                    <div style="min-height: 55px;"></div>
                    ลงชื่อ ................................................................<br>
                    ( <?php echo htmlspecialchars($evaluator ?: '................................................................'); ?> )<br>
                    <strong>ผู้ตรวจรับรอง</strong><br>
                    วันที่ <?php echo $evalDateLong; ?>
                </td>
                <td>
                    <div style="min-height: 55px;"></div>
                    ลงชื่อ ................................................................<br>
                    ( <?php echo htmlspecialchars($director ?: '................................................................'); ?> )<br>
                    <strong>ผู้อำนวยการ / ประธานกรรมการ</strong><br>
                    วันที่ <?php echo $evalDateLong; ?>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
