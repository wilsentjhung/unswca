<?php
    include("pgsql.php");

    // Courses information --------------------------------------------------------------------------
    $index = 0;
    $subjects = array("");
    $marks = array("");
    $grades = array("");
    $query = "SELECT tr.code AS tr_code, tr.title AS tr_title, tr.mark AS tr_mark, tr.grade AS tr_grade, tr.term AS tr_term,
                   s.uoc AS s_uoc, t.id
            FROM people p, transcript tr, subjects s, terms t
            WHERE p.id = $login_session AND p.id = tr.student_id AND tr.code LIKE s.code AND tr.term LIKE t.code AND tr.grade <> 'LE' AND tr.grade <> 'AF' AND tr.grade <> 'FL' AND tr.grade <> 'UF' AND tr.grade <> 'NC' AND tr.grade <> 'NF' AND tr.grade <> 'AW' AND tr.grade <> 'PW'
            ORDER BY t.id, tr.code";
    $result = pg_query($sims_db_connection, $query);

    while ($rows = pg_fetch_array($result)) {
        $subjects[$index] = $rows["tr_code"];
        $marks[$index] = $rows["tr_mark"];
        $grades[$index] = $rows["tr_grade"];
        $index++;
    }

    //assumes the same index corresponds for courses_passed, marks and grades.
    //TODO UOC subject checking
    //TODO degree type checking (MARKETING_HONOURS etc.)
    function check_pre_req ($courses_passed, $courses_marks, $courses_grades, $course_to_check, $uoc, $wam, $stream, $program_code, $career) {
        //get the pre req conditions
        $pre_req_query = "SELECT p.norm_pre_req_conditions AS prereq 
                         FROM pre_reqs p 
                         WHERE p.course_code = $course_to_check AND p.career = $career";
        $pre_req_result = pg_query($aims_db_connection, $pre_req_query);

        $pre_req_condition = explode(" ", $pre_req_result);
        $i = 0;
        $pre_req_evaluation = array("");
        while ($i < count($pre_req_condition)) {

            //checking individual subject
            if (preg_match("/^[A-Z]{4}[0-9]{4}$/", $pre_req_condition[$i])) {
                $course_counter = 0;
                while (($course_counter < count($courses_passed)) && !strcmp($pre_req_evaluation[$i], "TRUE")) {
                    if (strcmp($courses_passed[$course_counter], $pre_req_condition[$i])) {
                        $pre_req_evaluation[$i] = "TRUE";

                    }
                    $course_counter++;
                }
                if (!strcmp($pre_req_evaluation[$i], "TRUE")) {
                    $pre_req_evaluation[$i] = "FALSE";
                }

            //checking individual subject with a minimum grade
            } elseif (preg_match("/^[A-Z]{4}[0-9]{4}\{([A-Z0-9]{2})\}$/", $pre_req_condition[$i], $matches)) {
                $course_counter = 0;
                while ($course_counter < count($courses_passed)) {
                    if (strcmp($courses_passed[$course_counter], $pre_req_condition[$i])) {
                        if (preg_match("/^[A-Z]{4}[0-9]{4}\{([0-9]{1,3})\}$/", $matches[1])) {
                            if ($courses_marks[$course_counter] >= $matches[1]) {
                                $pre_req_evaluation[$i] = "TRUE";
                            }

                        } else {
                            if (strcmp($matches[1], "PS")) {
                                if (strcmp($courses_grades[$course_counter], "PS") || strcmp($courses_grades[$course_counter], "CR") || strcmp($courses_grades[$course_counter], "DN") || strcmp($courses_grades[$course_counter], "HD")) {
                                    $pre_req_evaluation[$i] = "TRUE";

                                }
                            } elseif (strcmp($matches[1], "CR")) {
                                if (strcmp($courses_grades[$course_counter], "CR") || strcmp($courses_grades[$course_counter], "DN") || strcmp($courses_grades[$course_counter], "HD")) {
                                    $pre_req_evaluation[$i] = "TRUE";
                                    
                                }
                            } elseif (strcmp($matches[1], "DN")) {
                                if (strcmp($courses_grades[$course_counter], "DN") || strcmp($courses_grades[$course_counter], "HD")) {
                                    $pre_req_evaluation[$i] = "TRUE";
                                    
                                }
                            } elseif (strcmp($matches[1], "HD")) {
                                if (strcmp($courses_grades[$course_counter], "HD")) {
                                    $pre_req_evaluation[$i] = "TRUE";
                                    
                                }
                            }
                        }

                    }
                    $course_counter++;
                }
                if (!strcmp($pre_req_evaluation[$i], "TRUE")) {
                    $pre_req_evaluation[$i] = "FALSE";
                }

            //for uoc checking
            } elseif (preg_match("/^([0-9]{1,3})_UOC$/", $pre_req_condition[$i], $matches)) {
                if ($uoc >= $matches[1]) {
                    $pre_req_evaluation[$i] = "TRUE";
                } else {
                    $pre_req_evaluation[$i] = "FALSE";
                }


            //for wam checking
            } elseif (preg_match("/^([0-9]{1,3})_WAM$/", $pre_req_condition[$i], $matches)) {
                if ($wam >= $matches[1]) {
                    $pre_req_evaluation[$i] = "TRUE";
                } else {
                    $pre_req_evaluation[$i] = "FALSE";
                }

            //program code checking
            } elseif (preg_match("/^([0-9]{4})$/", $pre_req_condition[$i], $matches)) {
                if ($program_code == $matches[1]) {
                    $pre_req_evaluation[$i] = "TRUE";
                } else {
                    $pre_req_evaluation[$i] = "FALSE";
                }

            //school approval
            } elseif (strcmp($pre_req_condition[$i], "SCHOOL_APPROVAL")) {
                $pre_req_evaluation[$i] = "FALSE";

            //things not handled yet
            } elseif (preg_match("/^([A-Z_a-z0-9]+$/", $pre_req_condition[$i])) {
                $pre_req_evaluation[$i] = "FALSE";
            }



            //for brackets and operators
            } else {
                $pre_req_evaluation[$i] = $pre_req_condition[$i];

            }
            $i++;
        }


        return eval("return " . implode(" ", $pre_req_evaluation))






    }


?>