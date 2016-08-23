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

    function calculate_uoc_courses ($courses_passed, $uoc_required, $pattern) {
        $k = 0;
        $uoc_acquired = 0;
        echo $pattern;
        while ($k < count($courses_passed)) {
            if (preg_match($pattern, $courses_passed[$k])) {
                //$uoc_acquired = $courses_passed[$k].uoc; 
            }
            $k++;

        }

        if ($uoc_acquired >= $uoc_required) {
            return "TRUE";
        }

        return "FALSE";

    }


    function error_checker ($code_to_check) {
        echo "$code_to_check";

        $checkResult = exec('echo \'<?php ' . $code_to_check . '\' | php -l >/dev/null 2>&1; echo $?');
        echo "$checkResult";
        if ($checkResult != 0) {
            $result = -1;
        } else {
            $result = eval($code_to_check);
        }
        return $result;

    }

    function has_done_course ($courses_passed, $course_to_check) {
        $course_counter = 0;
        #checking if the person has passed the subject before
        while ($course_counter < count($courses_passed)) {
            //echo $courses_passed[$course_counter];
            //echo $pre_req_condition[$i];
            if (strcmp($courses_passed[$course_counter], $course_to_check) == 0) {
                return 1;
            }
            $course_counter++;
        }

        return 0;
    }

    //assumes the same index corresponds for courses_passed, marks and grades.
    //TODO UOC subject checking
    //TODO degree type checking (MARKETING_HONOURS etc.)
    function check_pre_req ($courses_passed, $courses_marks, $courses_grades, $course_to_check, $uoc, $wam, $stream, $program_code, $career) {
        include("pgsql.php");

        //get the pre req conditions
        $pre_req_query = "SELECT p.norm_pre_req_conditions AS prereq 
                         FROM pre_reqs p 
                         WHERE p.course_code LIKE '$course_to_check' AND p.career LIKE '$career'";
        $result = pg_query($aims_db_connection, $pre_req_query);
        $pre_req_result = pg_fetch_array($result);
        $pre_req_condition = explode(" ", $pre_req_result[0]);
        $i = 0;
        $pre_req_evaluation = array("");
        
        

        //no prereq
        if (count($pre_req_condition) <= 1) {
            return 1;
        }
        
        while ($i < count($pre_req_condition)) {

            //checking individual subject
            //echo $pre_req_condition[$i];
            if (preg_match("/^[A-Z]{4}[0-9]{4}$/", $pre_req_condition[$i])) {
                $course_counter = 0;
                //echo count($courses_passed);
                $pre_req_evaluation[$i] = "FALSE";
                //echo $pre_req_evaluation[$i];
                while (($course_counter < count($courses_passed)) && !(strcmp($pre_req_evaluation[$i], "TRUE") == 0)) {
                    //echo $courses_passed[$course_counter];
                    //echo $pre_req_condition[$i];
                    if (strcmp($courses_passed[$course_counter], $pre_req_condition[$i]) == 0) {
                        $pre_req_evaluation[$i] = "TRUE";

                    //} elseif (check_equiv_req($courses_passed[$course_counter], $courses_marks, $courses_grades, $pre_req_condition[$i], $uoc, $wam, $stream, $program_code, $career) > 0) {
                    //    $pre_req_evaluation[$i] = "TRUE";

                    }
                    $course_counter++;
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
                //echo "===";
                //echo "$uoc $matches[1]";
                //echo "===";
                
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

            // UOC subject checking (12_UOC_LEVEL_1_CHEM, 6_UOC_LEVEL_1_BABS_BIOS)
            } elseif (preg_match("/^([0-9]{1,3})_UOC_LEVEL_([0-9])(_([A-Z]{4}))(_([A-Z]{4}))?$/", $pre_req_condition[$i], $matches)) {
                $uoc_required = $matches[1];
                $j = 4;
                $regex = "/^(";
                while ($j < count($matches)) {
                    if ($j > 4) {
                        $regex .= "|";
                    }
                    $regex .= $matches[$j];
                    $regex .= $matches[2];
                    $regex .= "...";
                    $j += 2;
                }
                $regex .= ")$/";
                $pre_req_evaluation[$i] = calculate_uoc_courses ($courses_passed, $uoc_required, $regex);


            //school approval
            } elseif (strcmp($pre_req_condition[$i], "SCHOOL_APPROVAL") == 0) {
                $pre_req_evaluation[$i] = "FALSE";

            //things not handled yet
            } elseif (preg_match("/^[A-Z_a-z0-9]+$/", $pre_req_condition[$i])) {
                $pre_req_evaluation[$i] = "FALSE";
            } //for brackets and operators
            else {
                //echo "bracket";
                $pre_req_evaluation[$i] = $pre_req_condition[$i];

            }

            //echo $pre_req_evaluation[$i];
            $i++;
        }
        //$eval = eval("return " . implode(" ", $pre_req_evaluation) . ";");
        //echo $eval;
        //echo eval("return " . "(1&&0)" . ";");

        $pre_req_evaluation_string = implode(" ", $pre_req_evaluation);
        echo $pre_req_result[0];

        echo $pre_req_evaluation_string;
        if (preg_match("/(FALSE|TRUE)\s*\(/i", $pre_req_evaluation_string)) {
            return -1;
        }
        return eval("return " . $pre_req_evaluation_string . ";");
    }

    //assumes the same index corresponds for courses_passed, marks and grades.
    //TODO UOC subject checking
    //TODO degree type checking (MARKETING_HONOURS etc.)
    function check_co_req ($courses_passed, $courses_marks, $courses_grades, $course_to_check, $uoc, $wam, $stream, $program_code, $career) {
        include("pgsql.php");

        //get the co_req conditions
        $co_req_query = "SELECT c.norm_co_req_conditions AS coreq 
                         FROM co_reqs c 
                         WHERE c.course_code LIKE '$course_to_check' AND c.career LIKE '$career'";
        $result = pg_query($aims_db_connection, $co_req_query);
        $co_req_result = pg_fetch_array($result);
        $co_req_condition = explode(" ", $co_req_result[0]);
        $i = 0;
        $co_req_evaluation = array("");

        //no co_req
        if (count($co_req_condition) <= 1) {
            return 1;
        }
        
        while ($i < count($co_req_condition)) {

            //checking individual subject
            //echo $co_req_condition[$i];
            if (preg_match("/^[A-Z]{4}[0-9]{4}$/", $co_req_condition[$i])) {
                $course_counter = 0;
                //echo count($courses_passed);
                $co_req_evaluation[$i] = "FALSE";
                //echo $co_req_evaluation[$i];
                while (($course_counter < count($courses_passed)) && !(strcmp($co_req_evaluation[$i], "TRUE") == 0)) {
                    //echo $courses_passed[$course_counter];
                    //echo $co_req_condition[$i];
                    if (strcmp($courses_passed[$course_counter], $co_req_condition[$i]) == 0) {
                        $co_req_evaluation[$i] = "TRUE";

                    //} elseif (check_equiv_req ($courses_passed[$course_counter], $courses_marks, $courses_grades, $co_req_condition[$i], $uoc, $wam, $stream, $program_code, $career) > 0) {
                    //    $co_req_evaluation[$i] = "TRUE";

                    }
                    $course_counter++;
                }

            //checking individual subject with a minimum grade
            } elseif (preg_match("/^[A-Z]{4}[0-9]{4}\{([A-Z0-9]{2})\}$/", $co_req_condition[$i], $matches)) {
                $course_counter = 0;
                while ($course_counter < count($courses_passed)) {
                    if (strcmp($courses_passed[$course_counter], $co_req_condition[$i])) {
                        if (preg_match("/^[A-Z]{4}[0-9]{4}\{([0-9]{1,3})\}$/", $matches[1])) {
                            if ($courses_marks[$course_counter] >= $matches[1]) {
                                $co_req_evaluation[$i] = "TRUE";
                            }

                        } else {
                            if (strcmp($matches[1], "PS")) {
                                if (strcmp($courses_grades[$course_counter], "PS") || strcmp($courses_grades[$course_counter], "CR") || strcmp($courses_grades[$course_counter], "DN") || strcmp($courses_grades[$course_counter], "HD")) {
                                    $co_req_evaluation[$i] = "TRUE";

                                }
                            } elseif (strcmp($matches[1], "CR")) {
                                if (strcmp($courses_grades[$course_counter], "CR") || strcmp($courses_grades[$course_counter], "DN") || strcmp($courses_grades[$course_counter], "HD")) {
                                    $co_req_evaluation[$i] = "TRUE";
                                    
                                }
                            } elseif (strcmp($matches[1], "DN")) {
                                if (strcmp($courses_grades[$course_counter], "DN") || strcmp($courses_grades[$course_counter], "HD")) {
                                    $co_req_evaluation[$i] = "TRUE";
                                    
                                }
                            } elseif (strcmp($matches[1], "HD")) {
                                if (strcmp($courses_grades[$course_counter], "HD")) {
                                    $co_req_evaluation[$i] = "TRUE";
                                    
                                }
                            }
                        }

                    }
                    $course_counter++;
                }
                if (!strcmp($co_req_evaluation[$i], "TRUE")) {
                    $co_req_evaluation[$i] = "FALSE";
                }

            //for uoc checking
            } elseif (preg_match("/^([0-9]{1,3})_UOC$/", $co_req_condition[$i], $matches)) {
                if ($uoc >= $matches[1]) {
                    $co_req_evaluation[$i] = "TRUE";
                } else {
                    $co_req_evaluation[$i] = "FALSE";
                }


            //for wam checking
            } elseif (preg_match("/^([0-9]{1,3})_WAM$/", $co_req_condition[$i], $matches)) {
                if ($wam >= $matches[1]) {
                    $co_req_evaluation[$i] = "TRUE";
                } else {
                    $co_req_evaluation[$i] = "FALSE";
                }

            //program code checking
            } elseif (preg_match("/^([0-9]{4})$/", $co_req_condition[$i], $matches)) {
                if ($program_code == $matches[1]) {
                    $co_req_evaluation[$i] = "TRUE";
                } else {
                    $co_req_evaluation[$i] = "FALSE";
                }

            //school approval
            } elseif (strcmp($co_req_condition[$i], "SCHOOL_APPROVAL") == 0) {
                $co_req_evaluation[$i] = "FALSE";

            //things not handled yet
            } elseif (preg_match("/^[A-Z_a-z0-9]+$/", $co_req_condition[$i])) {
                $co_req_evaluation[$i] = "FALSE";
            } //for brackets and operators
            else {
                //echo "bracket";
                $co_req_evaluation[$i] = $co_req_condition[$i];

            }

            //echo $co_req_evaluation[$i];
            $i++;
        }
        //$eval = eval("return " . implode(" ", $co_req_evaluation) . ";");
        //echo $eval;
        //echo eval("return " . "(1&&0)" . ";");
        $co_req_evaluation_string = implode(" ", $co_req_evaluation);

        echo $co_req_result[0];

        echo $co_req_evaluation_string;
        if (preg_match("/(FALSE|TRUE)\s*\(/i", $co_req_evaluation_string)) {
            return -1;
        }
        return eval("return " . implode(" ", $co_req_evaluation) . ";");
    }

    function check_equiv_req ($courses_passed, $courses_marks, $courses_grades, $course_to_check, $uoc, $wam, $stream, $program_code, $career) {
        include("pgsql.php");

        //get the equiv_req conditions
        $equiv_req_query = "SELECT eq.norm_equivalence_conditions AS equiv 
                         FROM equivalence eq
                         WHERE eq.course_code LIKE '$course_to_check' AND eq.career LIKE '$career'";
        $result = pg_query($aims_db_connection, $equiv_req_query);
        $equiv_req_result = pg_fetch_array($result);
        $equiv_req_condition = explode(" ", $equiv_req_result[0]);
        $i = 0;
        $equiv_req_evaluation = array("");

        //no equivalence
        if (count($equiv_req_condition) <= 1) {
            return 0;
        }
        
        while ($i < count($equiv_req_condition)) {

            //checking individual subject
            //echo $equiv_req_condition[$i];
            if (preg_match("/^[A-Z]{4}[0-9]{4}$/", $equiv_req_condition[$i])) {
                $course_counter = 0;
                //echo count($courses_passed);
                $equiv_req_evaluation[$i] = "FALSE";
                //echo $equiv_req_evaluation[$i];
                while (($course_counter < count($courses_passed)) && !(strcmp($equiv_req_evaluation[$i], "TRUE") == 0)) {
                    //echo $courses_passed[$course_counter];
                    //echo $equiv_req_condition[$i];
                    if (strcmp($courses_passed[$course_counter], $equiv_req_condition[$i]) == 0) {
                        $equiv_req_evaluation[$i] = "TRUE";

                    }
                    $course_counter++;
                }

            //things not handled yet
            } elseif (preg_match("/^[A-Z_a-z0-9]+$/", $equiv_req_condition[$i])) {
                $equiv_req_evaluation[$i] = "FALSE";
            } //for brackets and operators
            else {
                //echo "bracket";
                $equiv_req_evaluation[$i] = $equiv_req_condition[$i];

            }

            //echo $equiv_req_evaluation[$i];
            $i++;
        }
        //$eval = eval("return " . implode(" ", $equiv_req_evaluation) . ";");
        //echo $eval;
        //echo eval("return " . "(1&&0)" . ";");
        $equiv_req_evaluation_string = implode(" ", $equiv_req_evaluation);

        echo $equiv_req_result[0];

        echo $equiv_req_evaluation_string;
        if (preg_match("/(FALSE|TRUE)\s*\(/i", $equiv_req_evaluation_string)) {
            return -1;
        }
        return eval("return " . implode(" ", $equiv_req_evaluation) . ";");
    }

    function check_excl_req ($courses_passed, $courses_marks, $courses_grades, $course_to_check, $uoc, $wam, $stream, $program_code, $career) {
        include("pgsql.php");

        //get the excl_req conditions
        $excl_req_query = "SELECT ex.norm_exclusion_conditions AS excl 
                         FROM exclusion ex
                         WHERE ex.course_code LIKE '$course_to_check' AND ex.career LIKE '$career'";
        $result = pg_query($aims_db_connection, $excl_req_query);
        $excl_req_result = pg_fetch_array($result);
        $excl_req_condition = explode(" ", $excl_req_result[0]);
        $i = 0;
        $excl_req_evaluation = array("");
        

        //no exclusions
        if (count($excl_req_condition) <= 1) {
            return 0;
        }
        
        while ($i < count($excl_req_condition)) {

            //checking individual subject
            //echo $excl_req_condition[$i];
            if (preg_match("/^[A-Z]{4}[0-9]{4}$/", $excl_req_condition[$i])) {
                $course_counter = 0;
                //echo count($courses_passed);
                $excl_req_evaluation[$i] = "FALSE";
                //echo $excl_req_evaluation[$i];
                while (($course_counter < count($courses_passed)) && !(strcmp($excl_req_evaluation[$i], "TRUE") == 0)) {
                    //echo $courses_passed[$course_counter];
                    //echo $excl_req_condition[$i];
                    if (strcmp($courses_passed[$course_counter], $excl_req_condition[$i]) == 0) {
                        $excl_req_evaluation[$i] = "TRUE";

                    }
                    $course_counter++;
                }

            //things not handled yet
            } elseif (preg_match("/^[A-Z_a-z0-9]+$/", $excl_req_condition[$i])) {
                $excl_req_evaluation[$i] = "FALSE";
            } //for brackets and operators
            else {
                //echo "bracket";
                $excl_req_evaluation[$i] = $excl_req_condition[$i];

            }

            //echo $excl_req_evaluation[$i];
            $i++;
        }
        //$eval = eval("return " . implode(" ", $excl_req_evaluation) . ";");
        //echo $eval;
        //echo eval("return " . "(1&&0)" . ";");
        //echo implode(" ", $excl_req_evaluation);
        //echo error_checker("return " . implode(" ", $excl_req_evaluation) . ";");
        $excl_req_evaluation_string = implode(" ", $excl_req_evaluation);

        echo $excl_req_result[0];

        echo $excl_req_evaluation_string;
        if (preg_match("/(FALSE|TRUE)\s*\(/i", $excl_req_evaluation_string)) {
            return -1;
        }
        return eval("return " . implode(" ", $excl_req_evaluation) . ";");
    }    

    function suggest_courses ($start_course, $subjects, $marks, $grades, $totalUOC, $wam, $streams, $programs, $career) {
        include("pgsql.php");
        $course_query = "SELECT course_code
                             FROM equivalence eq
                             WHERE eq.course_code LIKE '%" . $start_course . "%' AND eq.career LIKE '$career'";

        echo $course_query;
        $result = pg_query($aims_db_connection, $course_query);
        
        $i = 0;
        echo pg_num_rows($result);
        echo "<h2>Courses</h2>";
        echo "<div><table class='table table-striped'>";
        echo "<thead><tr><th>Course</th><th>Info</th><th>Eligible</th></tr></thead>";
        echo "<tbody>";

        while ($i < pg_num_rows($result)) {
            $course_result = pg_fetch_array($result);
            
            $check_this_course = $course_result[0];
            echo "<tr><td>" . $check_this_course . "</td>";
            echo "<td><br>";
            $test_has_done_course = has_done_course($subjects, $check_this_course);
            
            if ($test_has_done_course == 1) {
                echo "done = TRUE";
                $test_has_done_course = 1;
            } elseif ($test_has_done_course == -1) {
                echo "done = ERROR";
            } else {
                $test_has_done_course = 0;
                echo "done = FALSE";
            }
            echo $test_has_done_course;
            echo "<br>";

            $test_pre = check_pre_req($subjects, $marks, $grades, $check_this_course, $totalUOC, $wam, $streams[0], $programs[0], $career);

            if ($test_pre == 1) {
                echo "prereq = TRUE";
                $test_pre = 1;
            } elseif ($test_pre == -1) {
                echo "prereq = ERROR";
            } else {
                $test_pre = 0;
                echo "prereq = FALSE";
            }
            echo $test_pre;
            echo "<br>";

            $test_co = check_co_req($subjects, $marks, $grades, $check_this_course, $totalUOC, $wam, $streams[0], $programs[0], $career);

            if ($test_co == 1) {
                echo "coreq = TRUE";
                $test_co = 1;
            } elseif ($test_co == -1) {
                echo "coreq = ERROR";
            } else {
                $test_co = 0;
                echo "coreq = FALSE";
            }
            echo $test_co;
            echo "<br>";

            $test_equiv = check_equiv_req($subjects, $marks, $grades, $check_this_course, $totalUOC, $wam, $streams[0], $programs[0], $career);

            if ($test_equiv == 1) {
                echo "equivalence = TRUE";
                $test_equiv = 1;
            } elseif ($test_equiv == -1) {
                echo "equivalence = ERROR";
            } else {
                $test_equiv = 0;
                echo "equivalence = FALSE";
            }
            echo $test_equiv;
            echo "<br>";

            $test_excl = check_excl_req($subjects, $marks, $grades, $check_this_course, $totalUOC, $wam, $streams[0], $programs[0], $career);

            if ($test_excl == 1) {
                echo "exclusion = TRUE";
                $test_excl = 1;
            } elseif ($test_excl == -1) {
                echo "exclusion = ERROR";
            } else {
                $test_excl = 0;
                echo "exclusion = FALSE";
            }
            echo $test_excl;
            echo "<br>";
            //echo gettype($test_has_done_course);
            //echo gettype($test_pre);
            //echo gettype($test_co);
            //echo gettype($test_equiv);
            //echo gettype($test_excl);
            echo "return " . "(" . "(!(" . $test_has_done_course . "))&&" . $test_pre . "&&" . $test_co . "&&" . "(!(" .$test_equiv . "))&&" . "(!(" . $test_excl . ")))" . ";";
            echo "<br>";
            if (($test_has_done_course == -1) || ($test_pre == -1) || ($test_co == -1) || ($test_equiv == -1) || ($test_excl == -1)){
                $test_final = -1;
            } else {
                $test_final = eval("return " . "(" . "(!(" . $test_has_done_course . "))&&" . $test_pre . "&&" . $test_co . "&&" . "(!(" .$test_equiv . "))&&" . "(!(" . $test_excl . ")))" . ";");
            }
            if ($test_final == 1) {
                echo "final = TRUE";
            } elseif ($test_final == -1) {
                echo "final = ERROR";
            } else {
                $test_final = 0;
                echo "final = FALSE";
            }
            echo "</td>";
            echo "<td>$test_final</td></tr>";
            $i++;
        }
        echo "</tbody>";
        echo "</table></div>";
    }

    suggest_courses("COMP", $subjects, $marks, $grades, $totalUOC, $wam, $streams, $programs, $career);
    //echo "<br>";
    //echo "LAWS";
    //echo "<br>";
    //suggest_courses("LAWS23", $subjects, $marks, $grades, $totalUOC, $wam, $streams, $programs, $career);
?>