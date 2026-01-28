<?php
/**
 * Notifier Helper - Google Chat Webhook Integration
 */

/**
 * Send notification to Google Chat
 * 
 * @param string $type Message type ('new' for new submission, 'update' for researcher update)
 * @param array $data Request data
 * @return bool True on success, false on failure
 */
function sendGoogleChat($type, $data)
{
    if (!defined('GOOGLE_CHAT_WEBHOOK') || empty(GOOGLE_CHAT_WEBHOOK)) {
        return false;
    }

    $title = ($type === 'new') ? "🚀 มีรายการขอรับรองงานวิจัยใหม่!" : "📝 นักวิจัยแก้ไขข้อมูลแล้ว";
    $subtitle = ($type === 'new') ? "แจ้งเจ้าหน้าที่เพื่อตรวจสอบเบื้องต้น" : "แจ้งเจ้าหน้าที่เพื่อตรวจสอบข้อมูลที่แก้ไข";
    $color = ($type === 'new') ? "#2e7d32" : "#1976d2"; // Green for new, Blue for update

    $request_id = $data['request_id'] ?? '-';
    $created_at = isset($data['created_at']) ? formatThaiDate($data['created_at']) : date('d/m/Y H:i');
    $project_title = $data['project_title'] ?? '-';
    $name = $data['name'] ?? '-';
    $affiliation = $data['affiliation'] ?? '-';
    $academic_position = $data['academic_position'] ?? '-';
    $branch = $data['branch'] ?? '-';
    $email = $data['email'] ?? '-';

    $payload = [
        "cardsV2" => [
            [
                "cardId" => "request_notification",
                "card" => [
                    "header" => [
                        "title" => $title,
                        "subtitle" => $subtitle,
                        "imageUrl" => "https://fonts.gstatic.com/s/i/googlematerialicons/notifications_active/v14/white-24dp/1x/outline_notifications_active_white_24dp.png",
                        "imageType" => "CIRCLE"
                    ],
                    "sections" => [
                        [
                            "header" => "รายละเอียดคำขอ",
                            "widgets" => [
                                [
                                    "decoratedText" => [
                                        "topLabel" => "รหัสคำขอ",
                                        "text" => "<b>$request_id</b>",
                                        "startIcon" => ["knownIcon" => "TICKET"]
                                    ]
                                ],
                                [
                                    "decoratedText" => [
                                        "topLabel" => "วันที่ส่ง",
                                        "text" => $created_at,
                                        "startIcon" => ["knownIcon" => "CLOCK"]
                                    ]
                                ],
                                [
                                    "decoratedText" => [
                                        "topLabel" => "ชื่อโครงการวิจัย",
                                        "text" => $project_title,
                                        "wrapText" => true,
                                        "startIcon" => ["knownIcon" => "DESCRIPTION"]
                                    ]
                                ],
                                [
                                    "decoratedText" => [
                                        "topLabel" => "ผู้ขอรับรอง",
                                        "text" => "<b>$name</b>",
                                        "startIcon" => ["knownIcon" => "PERSON"]
                                    ]
                                ],
                                [
                                    "decoratedText" => [
                                        "topLabel" => "สังกัด",
                                        "text" => $affiliation,
                                        "startIcon" => ["knownIcon" => "MEMBERSHIP"]
                                    ]
                                ],
                                [
                                    "decoratedText" => [
                                        "topLabel" => "ตำแหน่ง/สาขา",
                                        "text" => "$academic_position ($branch)",
                                        "startIcon" => ["knownIcon" => "STAR_CIRCLE"]
                                    ]
                                ],
                                [
                                    "decoratedText" => [
                                        "topLabel" => "อีเมล",
                                        "text" => $email,
                                        "startIcon" => ["knownIcon" => "EMAIL"]
                                    ]
                                ]
                            ]
                        ],
                        [
                            "widgets" => [
                                [
                                    "buttonList" => [
                                        "buttons" => [
                                            [
                                                "text" => "🔍 ดูรายละเอียดคำขอ",
                                                "onClick" => [
                                                    "openLink" => [
                                                        "url" => "https://newbri.mcu.ac.th/app/rsa/admin/view.php?id=" . ($data['id'] ?? '')
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    $json = json_encode($payload);

    $ch = curl_init(GOOGLE_CHAT_WEBHOOK);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json; charset=UTF-8',
        'Content-Length: ' . strlen($json)
    ]);

    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("Google Chat error: $error");
        return false;
    }

    return true;
}
