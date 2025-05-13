<?php
require_once 'config/database.php';

// 게시물 ID 가져오기
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// AJAX 댓글 추가/수정/삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'add_comment':
        case 'add_reply':
            $author = trim($_POST['author'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $depth = $parent_id ? 1 : 0;
            
            if ($author && $content) {
                $stmt = $pdo->prepare("INSERT INTO comments (post_id, parent_id, author, content, depth) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id, $parent_id, $author, $content, $depth]);
                
                // 새로 추가된 댓글 정보 반환
                $commentId = $pdo->lastInsertId();
                $stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ?");
                $stmt->execute([$commentId]);
                $newComment = $stmt->fetch();
                
                echo json_encode([
                    'success' => true,
                    'comment' => [
                        'id' => $newComment['id'],
                        'author' => htmlspecialchars($newComment['author']),
                        'content' => htmlspecialchars($newComment['content']),
                        'created_at' => date('Y-m-d H:i', strtotime($newComment['created_at'])),
                        'parent_id' => $newComment['parent_id']
                    ]
                ]);
                exit;
            }
            
            echo json_encode(['success' => false, 'message' => '작성자와 내용을 모두 입력해주세요.']);
            exit;
            
        case 'edit_comment':
            $commentId = (int)($_POST['comment_id'] ?? 0);
            $author = trim($_POST['author'] ?? '');
            $content = trim($_POST['content'] ?? '');
            
            if ($commentId && $author && $content) {
                // 해당 댓글이 현재 게시물의 댓글인지 확인
                $stmt = $pdo->prepare("SELECT id, parent_id FROM comments WHERE id = ? AND post_id = ? AND is_deleted = 0");
                $stmt->execute([$commentId, $id]);
                $comment = $stmt->fetch();
                
                if ($comment) {
                    $stmt = $pdo->prepare("UPDATE comments SET author = ?, content = ? WHERE id = ?");
                    $stmt->execute([$author, $content, $commentId]);
                    
                    echo json_encode([
                        'success' => true,
                        'comment' => [
                            'id' => $commentId,
                            'author' => htmlspecialchars($author),
                            'content' => htmlspecialchars($content),
                            'is_reply' => $comment['parent_id'] !== null
                        ]
                    ]);
                    exit;
                }
            }
            
            echo json_encode(['success' => false, 'message' => '댓글 수정에 실패했습니다.']);
            exit;
            
        case 'delete_comment':
            $commentId = (int)($_POST['comment_id'] ?? 0);
            
            if ($commentId) {
                // 해당 댓글이 현재 게시물의 댓글인지 확인
                $stmt = $pdo->prepare("SELECT id FROM comments WHERE id = ? AND post_id = ? AND is_deleted = 0");
                $stmt->execute([$commentId, $id]);
                if ($stmt->fetch()) {
                    // 댓글과 그에 달린 모든 대댓글을 함께 삭제 처리
                    $stmt = $pdo->prepare("
                        UPDATE comments 
                        SET is_deleted = 1 
                        WHERE id = ? OR parent_id = ?
                    ");
                    $stmt->execute([$commentId, $commentId]);
                    
                    echo json_encode(['success' => true]);
                    exit;
                }
            }
            
            echo json_encode(['success' => false, 'message' => '댓글 삭제에 실패했습니다.']);
            exit;
    }
}

// 조회수 증가
$stmt = $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
$stmt->execute([$id]);

// 게시물 조회
$stmt = $pdo->prepare("
    SELECT title, author, content, created_at, views 
    FROM posts 
    WHERE id = ? AND is_deleted = 0
");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: index.php');
    exit;
}

// 댓글 목록 조회 (계층형 구조로)
$stmt = $pdo->prepare("
    SELECT * FROM comments 
    WHERE post_id = ? AND is_deleted = 0 
    ORDER BY 
        CASE 
            WHEN parent_id IS NULL THEN id 
            ELSE parent_id 
        END ASC,
        created_at ASC
");
$stmt->execute([$id]);
$comments = $stmt->fetchAll();

// 댓글을 계층 구조로 재구성
$commentTree = [];
foreach ($comments as $comment) {
    if ($comment['parent_id'] === null) {
        $commentTree[$comment['id']] = [
            'comment' => $comment,
            'replies' => []
        ];
    } else {
        $commentTree[$comment['parent_id']]['replies'][] = $comment;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>게시물 조회</title>
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
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8 text-gray-800">게시물 조회</h1>
        
        <!-- 게시물 내용 -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <!-- 게시물 헤더 -->
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-800 mb-4"><?= htmlspecialchars($post['title']) ?></h2>
                <div class="flex items-center text-sm text-gray-600 space-x-4">
                    <span>작성자: <?= htmlspecialchars($post['author']) ?></span>
                    <span>작성일: <?= date('Y-m-d H:i', strtotime($post['created_at'])) ?></span>
                    <span>조회수: <?= $post['views'] ?></span>
                </div>
            </div>
            
            <!-- 게시물 본문 -->
            <div class="p-6">
                <div class="prose max-w-none">
                    <div class="text-gray-700">
                        <?= $post['content'] ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 댓글 섹션 -->
        <div class="mt-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">댓글</h2>
            
            <!-- 댓글 목록 -->
            <div class="space-y-4 mb-6" id="comments-container">
                <?php foreach ($commentTree as $commentData): ?>
                <div class="bg-white rounded-lg shadow-md p-4" id="comment-<?= $commentData['comment']['id'] ?>">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="font-medium text-gray-800"><?= htmlspecialchars($commentData['comment']['author']) ?></span>
                            <span class="text-sm text-gray-500 ml-2"><?= date('Y-m-d H:i', strtotime($commentData['comment']['created_at'])) ?></span>
                        </div>
                        <div class="space-x-2">
                            <button onclick="editComment(<?= $commentData['comment']['id'] ?>, '<?= htmlspecialchars(addslashes($commentData['comment']['author'])) ?>', '<?= htmlspecialchars(addslashes($commentData['comment']['content'])) ?>')" class="text-sm text-gray-500 hover:text-gray-700">수정</button>
                            <button onclick="deleteComment(<?= $commentData['comment']['id'] ?>)" class="text-sm text-red-500 hover:text-red-700">삭제</button>
                        </div>
                    </div>
                    <p class="text-gray-700"><?= nl2br(htmlspecialchars($commentData['comment']['content'])) ?></p>
                    
                    <!-- 답글 달기 버튼 -->
                    <div class="mt-2">
                        <button onclick="showReplyForm(<?= $commentData['comment']['id'] ?>)" class="text-sm text-primary-600 hover:text-primary-700">
                            답글 달기
                        </button>
                    </div>

                    <!-- 답글 작성 폼 -->
                    <div id="reply-form-<?= $commentData['comment']['id'] ?>" class="mt-3 ml-8 hidden">
                        <form class="space-y-3 reply-form" onsubmit="submitReply(event, <?= $commentData['comment']['id'] ?>)">
                            <div>
                                <input type="text" name="author" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-transparent" placeholder="작성자" required>
                            </div>
                            <div>
                                <textarea name="content" rows="2" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-transparent" placeholder="답글을 입력하세요" required></textarea>
                            </div>
                            <div class="flex justify-end space-x-2">
                                <button type="button" onclick="hideReplyForm(<?= $commentData['comment']['id'] ?>)" class="px-3 py-1 text-sm bg-gray-500 text-white rounded-md hover:bg-gray-600">
                                    취소
                                </button>
                                <button type="submit" class="px-3 py-1 text-sm bg-primary-600 text-white rounded-md hover:bg-primary-700">
                                    답글 작성
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- 답글 목록 -->
                    <div class="mt-3 space-y-3" id="replies-<?= $commentData['comment']['id'] ?>">
                        <?php foreach ($commentData['replies'] as $reply): ?>
                        <div class="ml-8 bg-gray-50 rounded-lg p-3" id="comment-<?= $reply['id'] ?>">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="font-medium text-gray-800"><?= htmlspecialchars($reply['author']) ?></span>
                                    <span class="text-sm text-gray-500 ml-2"><?= date('Y-m-d H:i', strtotime($reply['created_at'])) ?></span>
                                </div>
                                <div class="space-x-2">
                                    <button onclick="editComment(<?= $reply['id'] ?>, '<?= htmlspecialchars(addslashes($reply['author'])) ?>', '<?= htmlspecialchars(addslashes($reply['content'])) ?>')" class="text-sm text-gray-500 hover:text-gray-700">수정</button>
                                    <button onclick="deleteComment(<?= $reply['id'] ?>)" class="text-sm text-red-500 hover:text-red-700">삭제</button>
                                </div>
                            </div>
                            <p class="text-gray-700"><?= nl2br(htmlspecialchars($reply['content'])) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- 댓글 작성 폼 -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h3 class="text-lg font-medium text-gray-800 mb-4" id="comment-form-title">댓글 작성</h3>
                <form id="comment-form" class="space-y-4" onsubmit="submitComment(event)">
                    <input type="hidden" id="comment_id" name="comment_id" value="">
                    <div>
                        <label for="comment_author" class="block text-sm font-medium text-gray-700 mb-2">작성자</label>
                        <input type="text" id="comment_author" name="author" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-transparent" placeholder="작성자를 입력하세요" required>
                    </div>
                    <div>
                        <label for="comment_content" class="block text-sm font-medium text-gray-700 mb-2">내용</label>
                        <textarea id="comment_content" name="content" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-transparent" placeholder="댓글을 입력하세요" required></textarea>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" id="cancel-edit" onclick="cancelEdit()" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 hidden">
                            취소
                        </button>
                        <button type="submit" id="submit-button" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">
                            댓글 작성
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- 버튼 영역 -->
        <div class="mt-6 flex justify-between">
            <a href="index.php" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                목록으로
            </a>
            <div class="space-x-2">
                <a href="write.php?id=<?= $id ?>" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">
                    수정
                </a>
                <button onclick="deletePost(<?= $id ?>)" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    삭제
                </button>
            </div>
        </div>
    </div>

    <script>
    function deletePost(id) {
        if (confirm('정말로 이 게시물을 삭제하시겠습니까?')) {
            location.href = `delete.php?id=${id}`;
        }
    }

    // 댓글 수정 함수
    function editComment(id, author, content) {
        document.getElementById('comment_id').value = id;
        document.getElementById('comment_author').value = author;
        document.getElementById('comment_content').value = content;
        document.getElementById('comment-form-title').textContent = '댓글 수정';
        document.getElementById('submit-button').textContent = '수정 완료';
        document.getElementById('cancel-edit').classList.remove('hidden');
        document.getElementById('comment-form').scrollIntoView({ behavior: 'smooth' });
    }

    // 댓글 수정 취소
    function cancelEdit() {
        document.getElementById('comment-form').reset();
        document.getElementById('comment_id').value = '';
        document.getElementById('comment-form-title').textContent = '댓글 작성';
        document.getElementById('submit-button').textContent = '댓글 작성';
        document.getElementById('cancel-edit').classList.add('hidden');
    }

    // 댓글 삭제 함수
    async function deleteComment(id) {
        if (!confirm('정말로 이 댓글을 삭제하시겠습니까?\n대댓글이 있는 경우 함께 삭제됩니다.')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'delete_comment');
            formData.append('comment_id', id);

            const response = await fetch('view.php?id=<?= $id ?>', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                const commentElement = document.getElementById(`comment-${id}`);
                // 일반 댓글인 경우 대댓글도 함께 삭제
                if (!commentElement.classList.contains('ml-8')) {
                    const repliesContainer = document.getElementById(`replies-${id}`);
                    if (repliesContainer) {
                        repliesContainer.remove();
                    }
                }
                commentElement.remove();
            } else {
                alert(result.message || '댓글 삭제에 실패했습니다.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('댓글 삭제 중 오류가 발생했습니다.');
        }
    }

    // 답글 폼 표시
    function showReplyForm(commentId) {
        document.getElementById(`reply-form-${commentId}`).classList.remove('hidden');
    }

    // 답글 폼 숨기기
    function hideReplyForm(commentId) {
        document.getElementById(`reply-form-${commentId}`).classList.add('hidden');
    }

    // 답글 제출
    async function submitReply(event, parentId) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        formData.append('action', 'add_reply');
        formData.append('parent_id', parentId);

        try {
            const response = await fetch('view.php?id=<?= $id ?>', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                // 새 답글 HTML 생성
                const replyHtml = `
                    <div class="ml-8 bg-gray-50 rounded-lg p-3" id="comment-${result.comment.id}">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <span class="font-medium text-gray-800">${result.comment.author}</span>
                                <span class="text-sm text-gray-500 ml-2">${result.comment.created_at}</span>
                            </div>
                            <div class="space-x-2">
                                <button onclick="editComment(${result.comment.id}, '${result.comment.author}', '${result.comment.content}')" class="text-sm text-gray-500 hover:text-gray-700">수정</button>
                                <button onclick="deleteComment(${result.comment.id})" class="text-sm text-red-500 hover:text-red-700">삭제</button>
                            </div>
                        </div>
                        <p class="text-gray-700">${result.comment.content}</p>
                    </div>
                `;

                // 답글 목록에 새 답글 추가
                const repliesContainer = document.getElementById(`replies-${parentId}`);
                repliesContainer.insertAdjacentHTML('beforeend', replyHtml);

                // 폼 초기화 및 숨기기
                form.reset();
                hideReplyForm(parentId);
            } else {
                alert(result.message || '답글 작성에 실패했습니다.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('답글 작성 중 오류가 발생했습니다.');
        }
    }

    // 댓글 제출
    async function submitComment(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const commentId = formData.get('comment_id');
        formData.append('action', commentId ? 'edit_comment' : 'add_comment');

        try {
            const response = await fetch('view.php?id=<?= $id ?>', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                if (commentId) {
                    // 댓글 수정인 경우
                    const commentElement = document.getElementById(`comment-${commentId}`);
                    commentElement.querySelector('.font-medium').textContent = result.comment.author;
                    commentElement.querySelector('.text-gray-700').textContent = result.comment.content;
                    
                    // 수정 완료 후 폼 초기화
                    cancelEdit();
                    
                    // 수정된 댓글로 스크롤
                    commentElement.scrollIntoView({ behavior: 'smooth' });
                } else {
                    // 새 댓글 작성인 경우
                    const commentHtml = `
                        <div class="bg-white rounded-lg shadow-md p-4" id="comment-${result.comment.id}">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="font-medium text-gray-800">${result.comment.author}</span>
                                    <span class="text-sm text-gray-500 ml-2">${result.comment.created_at}</span>
                                </div>
                                <div class="space-x-2">
                                    <button onclick="editComment(${result.comment.id}, '${result.comment.author}', '${result.comment.content}')" class="text-sm text-gray-500 hover:text-gray-700">수정</button>
                                    <button onclick="deleteComment(${result.comment.id})" class="text-sm text-red-500 hover:text-red-700">삭제</button>
                                </div>
                            </div>
                            <p class="text-gray-700">${result.comment.content}</p>
                            <div class="mt-2">
                                <button onclick="showReplyForm(${result.comment.id})" class="text-sm text-primary-600 hover:text-primary-700">
                                    답글 달기
                                </button>
                            </div>
                            <div id="reply-form-${result.comment.id}" class="mt-3 ml-8 hidden">
                                <form class="space-y-3 reply-form" onsubmit="submitReply(event, ${result.comment.id})">
                                    <div>
                                        <input type="text" name="author" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-transparent" placeholder="작성자" required>
                                    </div>
                                    <div>
                                        <textarea name="content" rows="2" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-transparent" placeholder="답글을 입력하세요" required></textarea>
                                    </div>
                                    <div class="flex justify-end space-x-2">
                                        <button type="button" onclick="hideReplyForm(${result.comment.id})" class="px-3 py-1 text-sm bg-gray-500 text-white rounded-md hover:bg-gray-600">
                                            취소
                                        </button>
                                        <button type="submit" class="px-3 py-1 text-sm bg-primary-600 text-white rounded-md hover:bg-primary-700">
                                            답글 작성
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="mt-3 space-y-3" id="replies-${result.comment.id}">
                            </div>
                        </div>
                    `;

                    const container = document.getElementById('comments-container');
                    container.insertAdjacentHTML('beforeend', commentHtml);
                    form.reset();
                }
            } else {
                alert(result.message || '댓글 작성에 실패했습니다.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('댓글 작성 중 오류가 발생했습니다.');
        }
    }
    </script>
</body>
</html> 