#!/usr/bin/python

import urllib2
import re
#from bs4 import BeautifulSoup

allCourseCode = set()
codePattern = re.compile(r"[A-Z]{4}[0-9]{4}")
prereqSentence = re.compile(r"<p>[pP]re.*?<\/p>")
prereqOnlySentence = re.compile(r"<p>([pP]re.*?)([pP]requisite)?([cC]o-?[Rr]eq.*?)?([Ee]xcl.*?)?<\/p>")
exclOnlySentence = re.compile(r"<p>[pP]re.*?([Ee]xcl.*?)<\/p>")

f = open("pre_reqs.sql", "w")
f.write("DROP TABLE IF EXISTS pre_reqs;\n")
f.write("CREATE TABLE pre_reqs (course_code text, career text, pre_req_condtions text, norm_pre_req_conditions text);\n")

ugUrl = "http://www.handbook.unsw.edu.au/vbook2016/brCoursesByAtoZ.jsp?StudyLevel=Undergraduate&descr=All"
ugHtml = urllib2.urlopen(ugUrl).read()
subjectCodeWebsite = re.compile(r"http://www.handbook.unsw.edu.au/undergraduate/courses/2016/[A-Z]{4}[0-9]{4}\.html")
subjectCode = re.findall(subjectCodeWebsite, ugHtml)

pgUrl = "http://www.handbook.unsw.edu.au/vbook2016/brCoursesByAtoZ.jsp?StudyLevel=Postgraduate&descr=All"
pgHtml = urllib2.urlopen(pgUrl).read()
subjectCodeWebsite = re.compile(r"http://www.handbook.unsw.edu.au/postgraduate/courses/2016/[A-Z]{4}[0-9]{4}\.html")
subjectCode += re.findall(subjectCodeWebsite, pgHtml)

ugUrl = "http://www.handbook.unsw.edu.au/vbook2015/brCoursesByAtoZ.jsp?StudyLevel=Undergraduate&descr=All"
ugHtml = urllib2.urlopen(ugUrl).read()
subjectCodeWebsite = re.compile(r"http://www.handbook.unsw.edu.au/undergraduate/courses/2015/[A-Z]{4}[0-9]{4}\.html")
subjectCode += re.findall(subjectCodeWebsite, ugHtml)

pgUrl = "http://www.handbook.unsw.edu.au/vbook2015/brCoursesByAtoZ.jsp?StudyLevel=Postgraduate&descr=All"
pgHtml = urllib2.urlopen(pgUrl).read()
subjectCodeWebsite = re.compile(r"http://www.handbook.unsw.edu.au/postgraduate/courses/2015/[A-Z]{4}[0-9]{4}\.html")
subjectCode += re.findall(subjectCodeWebsite, pgHtml)

for hc in subjectCode:
	career = "UG"
	if "postgraduate" in hc:
		career = "PG"

	url2 = hc
	print hc
	try:
		codeInUrl = re.findall(codePattern, url2)
		html2 = urllib2.urlopen(url2).read()
		courseCode = re.findall(prereqSentence, html2)

		if courseCode:
			prereq = prereqOnlySentence.search(courseCode[0]).group(1)
			prereqCondition = prereq
			prereqCondition = re.sub(r"\'", "\'\'", prereqCondition, flags=re.IGNORECASE)

			#remove prereq word
			prereq = re.sub(r"Pre(.*?:|requisite)", "(", prereq, flags=re.IGNORECASE)

			#change to ands
			prereq = re.sub(r"\sAND\s", " && ", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r"\s&\s", " && ", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r"\sincluding\s", " && ", prereq, flags=re.IGNORECASE)

			#comma can mean and/or
			prereq = re.sub(r",\s*or", " || ", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r",", " && ", prereq, flags=re.IGNORECASE)

			#change to ors
			prereq = re.sub(r';', ' || ', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\sOR\s', ' || ', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\/', ' || ', prereq, flags=re.IGNORECASE)

			#change to uoc
			prereq = re.sub(r'\s*uc\b', '_UOC', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\s*uoc\b', '_UOC', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\s*unit.*? of credit.*?\b', '_UOC ', prereq, flags=re.IGNORECASE)

			#remove unnecessary words
			prereq = re.sub(r'\.', '', prereq, flags=re.IGNORECASE)

			#change [] to ()
			prereq = re.sub(r'\[', '(', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\]', ')', prereq, flags=re.IGNORECASE)



			prereq = re.sub(r'Enrolment in [^(]+ \(([^)]+)\)', r'\1', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'approval from the School', "SCHOOL_APPROVAL", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'school approval', "SCHOOL_APPROVAL", prereq, flags=re.IGNORECASE)
			#prereq = re.sub(r'Enrolment in Program 3586 && 3587 && 3588 && 3589 && 3155 && 3154 or 4737', "3586 || 3587 || 3588 || 3589 || 3155 || 3154 || 4737", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'Enrolment in program ([0-9]+)', r'\1', prereq, flags=re.IGNORECASE)

			#prereq = re.sub(r'and in any of the following plans MATHR13986, MATHR13523, MATHR13564, MATHR13956, MATHR13589, MATHR13761, MATHR13946, MATHR13949 \|\| MATHR13998', 
				#"(MATHR13986 || MATHR13523 || MATHR13564 || MATHR13956 || MATHR13589 || MATHR13761 || MATHR13946 || MATHR13949 || MATHR13998)", prereq, flags=re.IGNORECASE)
			#prereq = re.sub(r'A pass in BABS1201 plus either a pass in', "BABS1201 && (", prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'a minimum of a credit in ([A-Za-z]{4}[0-9]{4})', r'\1{CR}', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'([0-9]+)\s+UOC\s+at Level 1', r'\1_UOC_LEVEL_1', prereq, flags=re.IGNORECASE)

			prereq = re.sub(r'stream', '', prereq, flags=re.IGNORECASE)

			#cleanup
			prereq = re.sub(r'&&\s*&&$', '&&', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'&&\s*$', ' ', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\|\|\s*$', ' ', prereq, flags=re.IGNORECASE)
			prereq += ")"
			prereq = re.sub(r'\(\s*\)', ' ', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\(\s*', '(', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\s*\)', ')', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\s\s+', ' ', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\'', '\'\'', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'^\s+$', '', prereq, flags=re.IGNORECASE)
			#print prereq


			#manual
			if (codeInUrl[0] == "ACCT2507"):
				prereq = "(ACCT1511{80})"
			elif (codeInUrl[0] == "ACCT4794" or codeInUrl[0] == "ACCT4809" or codeInUrl[0] == "ACCT4851" or
				codeInUrl[0] == "ACCT4852" or codeInUrl[0] == "ACCT4897"):
				#!!!
				prereq = "Admission to Honours level majoring in Accounting."
			elif (codeInUrl[0] == "ACTL1101"):
				prereq = "(MATH1151 && (3586 || 3587 || 3588 || 3589 || 3155 || 3154 || 4737))"
			elif (codeInUrl[0] == "ACTL2102"):
				prereq = "((ACTL2131 || MATH2901) && (3154 || 3155 || 3586 || 3587 || 3588 || 3589 || 4737))"
			elif (codeInUrl[0] == "ACTL3162"):
				prereq = "(ACTL2102 || (MATH2901 && (MATHR13986 || MATHR13523 || MATHR13564 || MATHR13956 || MATHR13589 || MATHR13761 || MATHR13946 || MATHR13949 || MATHR13998)))"
			elif (codeInUrl[0] == "ACTL4000" or codeInUrl[0] == "ACTL4003"):
				#!!!
				prereq = "Admission to BCom Hons in Actuarial Studies"
			elif (codeInUrl[0] == "ACTL4002"):
				#????
			elif (codeInUrl[0] == "ANAT2111"):
				prereq = "(BABS1201 && (ANAT2241 || BABS1202 || BABS2202 || BABS2204 || BIOC2201 || BIOC2291 || BIOS1101 || HESC1501 || PHSL2101 || PHSL2121 || PHSL2501 || VISN1101))"
			elif (codeInUrl[0] == "ANAT2241"):
				prereq = "(BABS1201 && Program_WAM_55)"
			elif (codeInUrl[0] == "ARCH1201"):
				prereq = "((ARCH1101 && ARCH1102) || ARCH1390)"
			elif (codeInUrl[0] ==  "ARCH1302"):
				prereq = "(ARCH1202 && ARCH1301)"
			elif (codeInUrl[0] ==  "ARCH1395"):
				prereq = "(ARCH1394 && ARCH1384)"
			elif (codeInUrl[0] == "ARTS2006"):
				prereq = "(MUSC1704 || MUSC1705 || MUSC1706 || ARTS1005 || 3425 || 3426 || 3427 || 3448 || 3449)"
			elif (codeInUrl[0] == "ARTS2038"):
				prereq = "(30_UOC && 12_UOC_LEVEL_1_ENGLISH)"
			elif (codeInUrl[0] == "ARTS2050"):
				#!!!
				prereq = "(12_UOC_LEVEL_1 && (enrolment in an Arts and Social Sciences || Art and Design program))"
			elif (codeInUrl[0] == "ARTS2065"):
				prereq = "(30_UOC_LEVEL_1 && (ARTS1060 || ARTS1062))"
			elif (codeInUrl[0] == "ARTS2452"):
				#???
				prereq = "(ARTS3451 || ARTS3452 || ARTS3453)"
			elif (codeInUrl[0] == "ARTS2690" or codeInUrl[0] == "ARTS2692" or codeInUrl[0] == "ARTS2693" 
				or codeInUrl[0] == "ARTS2694" or codeInUrl[0] == "ARTS2696"):
				prereq = "(30_UOC && 12_UOC_LEVEL_1_LINGUISTICS)"

			##ARTS[34]### skipped
			elif (codeInUrl[0] == "ATSI3008"):
				#!!!
				prereq = "(120_UOC && enrolment in a major in Indigenous Studies && enrolled in the final semester of an Arts Faculty)"
			elif (codeInUrl[0] == "AVIA2013"):
				prereq = "(18_UOC from either AVIA1401 || AVIA1901 || MATH1041 || PHYS1211)"
			elif (codeInUrl[0] == "AVIA3101"):
				prereq = "(AVIA1900 || AVIA2004 || AVIA2014 || AVIA1901)"
			elif (codeInUrl[0] == "AVIA3851"):
				prereq = "(AVIA1850 || AVIA2701)"
			elif (codeInUrl[0] == "BABS2011"):
				#!!!
				prereq = "(BABS1201 || BABS1202 && enrolment in relevant Science program)"
			elif (codeInUrl[0] == "BABS2202"):
				prereq = "(BABS1201 && (CHEM1011 || CHEM1031))"
			elif (codeInUrl[0] == "BABS3021"):
				prereq = "(12_UOC from MICR2011 || BIOS2021 || BABS2204 || BIOS2621 || BABS2264 || BIOC2201)"

			elif (codeInUrl[0] == "BABS3631" or codeInUrl[0] == "BABS3031"):
				prereq = "48_UOC"
			elif (codeInUrl[0] == "BABS3041"):
				prereq = "(BIOC2101 || (BIOC2181 && MICR2011) || (BIOC2181 && BABS2202))"
			elif (codeInUrl[0] == "BABS3061" or codeInUrl[0] == "BABS3121" or codeInUrl[0] == "BIOC3111"):
				prereq = "((BIOC2101 || LIFE2101) && BIOC2201)"
			elif (codeInUrl[0] == "BABS3621"):
				prereq = "(BIOC2101 && BIOC2201 && (3985 || 3990 || 3972 || 3973 || 3986 || 3931 || 3936))"
			elif (codeInUrl[0] == "BABS4053"):
				prereq = "(144_UOC && 3052)"
			elif (codeInUrl[0] == "BEES6741"):
				prereq = "30_UOC_SCIENCE"
			elif ((re.match('BEIL', codeInUrl[0]) or re.match('BEIL', codeInUrl[0])) and re.match('96_UOC completed in Built Environment', prereq)):
				prereq = "96_UOC_BUILT_ENVIORNMENT"
			elif ((re.match('BEIL', codeInUrl[0]) or re.match('BEIL', codeInUrl[0])) and re.match('96_UOC completed', prereq)):
				prereq = "96_UOC"
			elif (codeInUrl[0] == "BINF4910"):
				prereq = "(126 UOC && (3647 || 3755 || 3756 || 3757 || 3715))"
			elif (codeInUrl[0] == "BINF4920"):
				prereq = "(3647 || 3755 || 3756 || 3757 || 3715)"
			elif (codeInUrl[0] == "BIOC2101" or codeInUrl[0] == "BIOC2201"):
				prereq = "(BABS1201 && (CHEM1011 || CHEM1031 || CHEM1051) && (CHEM1021 || CHEM1041 || CHEM1061))"
			elif (codeInUrl[0] == "BIOC2291"):
				prereq = "(BABS1201 && (CHEM1011 || CHEM1031))"





			

			f.write("INSERT INTO pre_reqs (course_code, career, pre_req_condtions, norm_pre_req_conditions) SELECT \'%s\', \'%s\', \'%s\', \'%s\' WHERE NOT EXISTS (SELECT course_code, career FROM pre_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], career, prereqCondition, prereq, codeInUrl[0], career))
         
			#print prereq[0].group()
		else:
			#print "went here"
			f.write("INSERT INTO pre_reqs (course_code, career, pre_req_condtions, norm_pre_req_conditions) SELECT \'%s\', \'%s\', \'\', \'\' WHERE NOT EXISTS (SELECT course_code, career FROM pre_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], career, codeInUrl[0], career))

	except:
		print codeInUrl,
		print "No Handbook Entry"

f.close()