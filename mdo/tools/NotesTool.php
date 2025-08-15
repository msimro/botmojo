<?php
/**
 * NotesTool - Note Taking and Knowledge Management for MDO
 */
class NotesTool {
    public function execute(array $params) {
        $userId = $params['user_id'] ?? 'default_user';
        $requestType = $params['request_type'] ?? 'get_notes';
        
        // Simplified mock data for POC
        if ($requestType === 'get_notes') {
            $topic = $params['topic'] ?? '';
            
            $allNotes = [
                [
                    'id' => 'note1',
                    'title' => 'Machine Learning Concepts',
                    'content' => 'Key ML concepts include supervised learning, unsupervised learning, and reinforcement learning...',
                    'topic' => 'computer science',
                    'tags' => ['machine learning', 'AI', 'data science'],
                    'created' => '2025-07-15',
                    'updated' => '2025-07-20'
                ],
                [
                    'id' => 'note2',
                    'title' => 'Spanish Verb Conjugations',
                    'content' => 'Present tense regular -ar verbs: hablo, hablas, habla, hablamos, habláis, hablan...',
                    'topic' => 'languages',
                    'tags' => ['spanish', 'grammar', 'verbs'],
                    'created' => '2025-08-01',
                    'updated' => '2025-08-01'
                ],
                [
                    'id' => 'note3',
                    'title' => 'Project Management Principles',
                    'content' => 'The triple constraint: scope, time, and cost. Quality is affected by all three...',
                    'topic' => 'business',
                    'tags' => ['project management', 'leadership'],
                    'created' => '2025-08-05',
                    'updated' => '2025-08-07'
                ]
            ];
            
            // Filter by topic if provided
            if (!empty($topic)) {
                $filteredNotes = [];
                
                foreach ($allNotes as $note) {
                    if (strtolower($note['topic']) === strtolower($topic) || 
                        in_array(strtolower($topic), array_map('strtolower', $note['tags']))) {
                        $filteredNotes[] = $note;
                    }
                }
                
                return $filteredNotes;
            }
            
            return $allNotes;
        } elseif ($requestType === 'save_note') {
            $noteData = $params['note_data'] ?? [];
            $title = $noteData['title'] ?? 'Untitled Note';
            
            return [
                'status' => 'success',
                'message' => "Saved note: {$title}",
                'saved_at' => date('Y-m-d H:i:s'),
                'note_id' => 'note' . rand(100, 999)
            ];
        } elseif ($requestType === 'get_related_resources') {
            $noteId = $params['note_id'] ?? '';
            $topic = $params['topic'] ?? '';
            
            return [
                [
                    'type' => 'article',
                    'title' => 'Introduction to ' . ($topic ?: 'the Topic'),
                    'url' => 'https://example.com/intro',
                    'source' => 'Learning Platform',
                    'relevance' => 0.92
                ],
                [
                    'type' => 'video',
                    'title' => ($topic ?: 'Topic') . ' Explained',
                    'url' => 'https://example.com/explained',
                    'duration' => '15:42',
                    'source' => 'Educational Channel',
                    'relevance' => 0.85
                ],
                [
                    'type' => 'course',
                    'title' => ($topic ?: 'Subject') . ' Specialization',
                    'url' => 'https://example.com/course',
                    'platform' => 'Online Learning',
                    'cost' => 'Free',
                    'relevance' => 0.78
                ]
            ];
        } elseif ($requestType === 'search_notes') {
            $query = $params['query'] ?? '';
            
            return [
                [
                    'id' => 'note1',
                    'title' => 'Machine Learning Concepts',
                    'snippet' => '...Key ML concepts include supervised learning...',
                    'relevance' => 0.95
                ]
            ];
        }
        
        return [
            'status' => 'error',
            'message' => 'Invalid request type'
        ];
    }
}
