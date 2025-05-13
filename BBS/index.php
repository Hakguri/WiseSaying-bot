<?php
require_once 'config/database.php';

echo "<pre>DB_HOST: $db_host, DB_PORT: $db_port, DB_NAME: $db_name, DB_USER: $db_user</pre>";
exit;

// 페이지네이션 설정
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// 전체 게시물 수 조회
$stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE is_deleted = 0");
$total_posts = $stmt->fetchColumn();
$total_pages = ceil($total_posts / $per_page);

// 게시물 목록 조회 (각 게시물의 댓글 수도 함께 조회)
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id AND c.is_deleted = 0) as comment_count,
        CASE 
            WHEN (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id AND c.is_deleted = 0) >= 10 
            THEN 1 
            ELSE 0 
        END as is_hot
    FROM posts p
    WHERE p.is_deleted = 0 
    ORDER BY 
        is_hot DESC,
        p.id DESC 
    LIMIT ?, ?
");
$stmt->bindValue(1, $offset, PDO::PARAM_INT);
$stmt->bindValue(2, $per_page, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>게시판</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            600: '#1e3a8a', // Navy Blue
                            700: '#1e40af', // Darker Navy Blue
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes hotBlink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .hot-badge {
            animation: hotBlink 2s infinite;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">게시판</h1>
            <a href="write.php" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">
                글쓰기
            </a>
        </div>

        <!-- 게시물 목록 -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">번호</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">제목</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">작성자</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">작성일</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">조회수</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($posts as $post): ?>
                    <tr class="hover:bg-gray-50 <?= $post['is_hot'] ? 'bg-orange-50' : '' ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= $post['id'] ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="view.php?id=<?= $post['id'] ?>" class="text-primary-600 hover:text-primary-700 flex items-center gap-2">
                                <?php if ($post['is_hot']): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 hot-badge">
                                    HOT
                                </span>
                                <span class="font-medium">
                                    <?= htmlspecialchars($post['title']) ?>
                                </span>
                                <?php else: ?>
                                    <?= htmlspecialchars($post['title']) ?>
                                <?php endif; ?>
                                <?php if ($post['comment_count'] > 0): ?>
                                <span class="text-gray-500 <?= $post['is_hot'] ? 'font-medium text-red-500' : '' ?>">
                                    [<?= $post['comment_count'] ?>]
                                </span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= htmlspecialchars($post['author']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= date('Y-m-d', strtotime($post['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= $post['views'] ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- 페이지네이션 -->
        <div class="mt-6 flex justify-center">
            <nav class="flex items-center space-x-2">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" 
                       class="px-3 py-1 rounded-md <?= $i === $page ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </nav>
        </div>
    </div>
</body>
</html>
