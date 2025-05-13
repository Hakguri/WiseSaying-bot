<?php
require_once 'config/database.php';

// 수정 모드인 경우 게시물 정보 가져오기
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = null;

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND is_deleted = 0");
    $stmt->execute([$id]);
    $post = $stmt->fetch();

    if (!$post) {
        header('Location: index.php');
        exit;
    }
}

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title && $author && $content) {
        if ($id > 0) {
            // 게시물 수정
            $stmt = $pdo->prepare("
                UPDATE posts 
                SET title = ?, author = ?, content = ? 
                WHERE id = ?
            ");
            $stmt->execute([$title, $author, $content, $id]);
        } else {
            // 새 게시물 작성
            $stmt = $pdo->prepare("
                INSERT INTO posts (title, author, content) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$title, $author, $content]);
            $id = $pdo->lastInsertId();
        }

        header('Location: view.php?id=' . $id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? '게시물 수정' : '글쓰기' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/40.1.0/classic/ckeditor.js"></script>
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
        .ck-editor__editable {
            min-height: 400px;
        }
        .ck.ck-editor__main > .ck-editor__editable {
            background-color: white;
        }
        .ck.ck-toolbar {
            background-color: #f3f4f6;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8 text-gray-800"><?= $id ? '게시물 수정' : '글쓰기' ?></h1>
        
        <!-- 글쓰기 폼 -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <form class="p-6" method="POST">
                <!-- 제목 입력 -->
                <div class="mb-6">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">제목</label>
                    <input type="text" id="title" name="title" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-transparent"
                           placeholder="제목을 입력하세요"
                           value="<?= htmlspecialchars($post['title'] ?? '') ?>" required>
                </div>
                
                <!-- 작성자 입력 -->
                <div class="mb-6">
                    <label for="author" class="block text-sm font-medium text-gray-700 mb-2">작성자</label>
                    <input type="text" id="author" name="author" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-transparent"
                           placeholder="작성자를 입력하세요"
                           value="<?= htmlspecialchars($post['author'] ?? '') ?>" required>
                </div>
                
                <!-- 내용 입력 -->
                <div class="mb-6">
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">내용</label>
                    <div id="editor"><?= $post['content'] ?? '' ?></div>
                    <input type="hidden" name="content" id="content">
                </div>
                
                <!-- 버튼 영역 -->
                <div class="flex justify-end space-x-2">
                    <a href="<?= $id ? "view.php?id=$id" : 'index.php' ?>" 
                       class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                        취소
                    </a>
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">
                        <?= $id ? '수정' : '저장' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        ClassicEditor
            .create(document.querySelector('#editor'), {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'outdent', 'indent', '|', 'blockQuote', 'insertTable', 'undo', 'redo'],
                language: 'ko'
            })
            .then(editor => {
                // 폼 제출 시 에디터 내용을 hidden input에 저장
                const form = document.querySelector('form');
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    document.querySelector('#content').value = editor.getData();
                    this.submit();
                });
            })
            .catch(error => {
                console.error(error);
            });
    </script>
</body>
</html> 