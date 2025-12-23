<?php
/**
 * Helper to compute election results consistently across admin and voter views.
 */

if (!function_exists('getElectionResults')) {
    /**
     * Returns the election results grouped by position.
     *
     * Structure:
     * [
     *   'President' => [
     *       ['id' => 1, 'name' => 'Candidate', 'votes' => 10, 'percentage' => 50.0, 'profile_picture' => '...', 'is_blank' => false],
     *       ['id' => null, 'name' => 'Votes blancs', 'votes' => 2, 'percentage' => 10.0, 'profile_picture' => null, 'is_blank' => true],
     *   ],
     *   ...
     * ]
     */
    function getElectionResults(mysqli $conn, int $electionId): array
    {
        $results = [];

        // Retrieve candidates with their vote counts
        $stmt = $conn->prepare(
            "SELECT 
                c.id AS candidate_id,
                c.position,
                u.full_name,
                u.profile_picture,
                COALESCE(vote_counts.vote_count, 0) AS vote_count
            FROM candidates c
            INNER JOIN users u ON c.user_id = u.id
            LEFT JOIN (
                SELECT candidate_id, position, COUNT(*) AS vote_count
                FROM votes
                WHERE election_id = ? AND is_blank = 0
                GROUP BY candidate_id, position
            ) AS vote_counts ON vote_counts.candidate_id = c.id AND vote_counts.position = c.position
            WHERE c.election_id = ?
            ORDER BY c.position, vote_count DESC, u.full_name"
        );

        if (!$stmt) {
            throw new RuntimeException('Unable to prepare election results query: ' . $conn->error);
        }

        $stmt->bind_param('ii', $electionId, $electionId);
        $stmt->execute();
        $candidates = $stmt->get_result();

        while ($row = $candidates->fetch_assoc()) {
            $position = $row['position'];
            if (!isset($results[$position])) {
                $results[$position] = [];
            }

            $results[$position][] = [
                'id' => (int)$row['candidate_id'],
                'name' => $row['full_name'],
                'profile_picture' => $row['profile_picture'] ?? null,
                'votes' => (int)$row['vote_count'],
                'percentage' => 0,
                'is_blank' => false,
            ];
        }
        $stmt->close();

        // Retrieve blank votes by position
        $stmt = $conn->prepare(
            'SELECT position, COUNT(*) AS blank_votes
             FROM votes
             WHERE election_id = ? AND is_blank = 1
             GROUP BY position'
        );

        $stmt->bind_param('i', $electionId);
        $stmt->execute();
        $blankVotes = $stmt->get_result();

        while ($row = $blankVotes->fetch_assoc()) {
            $position = $row['position'];
            if (!isset($results[$position])) {
                $results[$position] = [];
            }

            $results[$position][] = [
                'id' => null,
                'name' => 'Votes blancs',
                'profile_picture' => null,
                'votes' => (int)$row['blank_votes'],
                'percentage' => 0,
                'is_blank' => true,
            ];
        }
        $stmt->close();

        // Compute percentages and sort candidates (blank votes last when tied)
        foreach ($results as $position => &$positionResults) {
            $totalVotes = array_sum(array_column($positionResults, 'votes'));

            foreach ($positionResults as &$candidate) {
                $candidate['percentage'] = $totalVotes > 0
                    ? round(($candidate['votes'] / $totalVotes) * 100, 1)
                    : 0.0;
            }
            unset($candidate);

            usort($positionResults, function (array $a, array $b) {
                if ($a['votes'] === $b['votes']) {
                    // Place blank votes after named candidates when vote counts tie
                    if ($a['is_blank'] === $b['is_blank']) {
                        return strcmp($a['name'], $b['name']);
                    }
                    return $a['is_blank'] ? 1 : -1;
                }
                return $b['votes'] <=> $a['votes'];
            });
        }
        unset($positionResults);

        return $results;
    }
}
