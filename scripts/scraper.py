#!/usr/bin/python

import urllib2
import re
#from bs4 import BeautifulSoup

#find how many courses left to go
#select count(*)  from pre_reqs where ltrim(pre_req_conditions) <> '' and course_code > 'CVEN1000';
#find the courses
#select course_code, career, left(pre_req_conditions,120) as c, left(norm_pre_req_conditions,120)  from pre_reqs where ltrim(pre_req_conditions) <> '' and course_code > 'CVEN1000';
#find a specific course to show the full length conditions
#select * from pre_reqs where course_code like '%COMP4930%';

allCourseCode = set()
codePattern = re.compile(r"[A-Z]{4}[0-9]{4}")
prereqSentence = re.compile(r"<p>[pP]re.*?<\/p>")
prereqOnlySentence = re.compile(r"<p>([pP]re.*?)([pP]requisite)?([cC]o-?[Rr]eq.*?)?([Ee]xcl.*?)?<\/p>")
exclOnlySentence = re.compile(r"<p>[pP]re.*?([Ee]xcl.*?)<\/p>")

f = open("pre_reqs.sql", "w")
f.write("DROP TABLE IF EXISTS pre_reqs;\n")
f.write("CREATE TABLE pre_reqs (course_code text, career text, pre_req_conditions text, norm_pre_req_conditions text);\n")

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
			prereq = re.sub(r'\s*uoc\b', '_UOC', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'\s*uc\b', '_UOC', prereq, flags=re.IGNORECASE)
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
			prereq = re.sub(r'([0-9]+)_UOC\s+at\s+Level\s+([0-9])\s*[^a-zA-Z0-9]*$', r'\1_UOC_LEVEL_\2', prereq, flags=re.IGNORECASE)
			prereq = re.sub(r'([0-9]+)_UOC\s+at\s+Level\s+([0-9])\s+([A-Za-z])', r'\1_UOC_LEVEL_\2_\3', prereq, flags=re.IGNORECASE)

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
				prereq = "???"
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
				prereq = "((AVIA1401 && AVIA1901 && MATH1041) || (AVIA1401 && AVIA1901 && PHYS1211) || (AVIA1401 && MATH1041 && PHYS1211) || (AVIA1901 && MATH1041 && PHYS1211))"
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
				prereq = "(48_UOC)"
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
			elif ((re.match('BEIL', codeInUrl[0]) or re.match('BEIL', codeInUrl[0]) or re.match('BLDG', codeInUrl[0])) and re.match('(96_UOC completed in Built Environment', prereq)):
				prereq = "(96_UOC_BUILT_ENVIORNMENT)"
			elif ((re.match('BEIL', codeInUrl[0]) or re.match('BEIL', codeInUrl[0])) and re.match('(96_UOC completed', prereq)):
				prereq = "(96_UOC)"
			elif (codeInUrl[0] == "BINF4910"):
				prereq = "(126 UOC && (3647 || 3755 || 3756 || 3757 || 3715))"
			elif (codeInUrl[0] == "BINF4920"):
				prereq = "(3647 || 3755 || 3756 || 3757 || 3715)"
			elif (codeInUrl[0] == "BIOC2101" or codeInUrl[0] == "BIOC2201"):
				prereq = "(BABS1201 && (CHEM1011 || CHEM1031 || CHEM1051) && (CHEM1021 || CHEM1041 || CHEM1061))"
			elif (codeInUrl[0] == "BIOC2291"):
				prereq = "(BABS1201 && (CHEM1011 || CHEM1031))"
			elif (codeInUrl[0] == "BIOC3671"):
				prereq = "(BIOC2101 && BIOC2201 && (3985 || 3990 || 3972 || 3973 || 3986 || 3931 || 3936))"
			elif (codeInUrl[0] == "BIOM5950"):
				prereq = "(126_UOC_3728 || 126_UOC_3757)"
			elif (codeInUrl[0] == "BIOM5960"):
				prereq = "(STAGE_4_3749)"
			elif (codeInUrl[0] == "BIOM9510"):
				prereq = "!(3710 || 3711 || 3683 || 3688)"
			elif (codeInUrl[0] == "BIOM9670"):
				prereq = "(BIOM9660{DN})"
			elif (codeInUrl[0] == "BIOS3711"):
				prereq = "((ANAT2111 || ANAT1521 || ANAT2511{CR} || ANAT1551) && PROGRAM_WAM_50)"
			elif (codeInUrl[0] == "CEIC2004"):
				prereq = "(CHEM1021 || CHEM1041 || CEIC1001)"
			elif ((re.match('CEIC', codeInUrl[0]) or re.match('CHEM', codeInUrl[0]) or re.match('CHEN', codeInUrl[0])) and re.match('(at least 144 Units', prereq)):
				prereq = "(144_UOC_CHEMICAL_ENGINEERING || 144_UOC_INDUSTRIAL_CHEMICAL"
			elif (codeInUrl[0] == "CEIC6005"):
				prereq = "((MATS1101 || CHEM1011 || CHEM1021) && CEIC2000 && CEIC2002)"
			elif (codeInUrl[0] == "CHEM1041"):
				prereq = "(CHEM1031 || CHEM1011{CR})"
			elif (codeInUrl[0] == "CHEM1051"):
				prereq = "(3999 || 3992)"
			elif (codeInUrl[0] == "CHEM1061"):
				prereq = "((3992 || 3999) && (CHEM1051 || CHEM1031 || CHEM1011{CR}))"
			elif (codeInUrl[0] == "CHEM1829"):
				#!!!
				prereq = "(CHEM1031 && (3952 || VISION_SCIENCE))"
			elif (codeInUrl[0] == "CHEM2011"):
				prereq = "((CHEM1011 || CHEM1031 || CHEM1051) && (CHEM1021 || CHEM1041 || CHEM1061) && (MATH1011 || MATH1031 || MATH1131 || MATH1141 || MATH1231 || MATH1241))"
			elif (codeInUrl[0] == "CHEM2021" or codeInUrl[0] == "CHEM2031" or codeInUrl[0] == "CHEM2921"):
				prereq = "((CHEM1011 || CHEM1031 || CHEM1051) && (CHEM1021 || CHEM1041 || CHEM1061))"
			elif (codeInUrl[0] == "CHEM2041"):
				prereq = "((CHEM1011 || CHEM1031 || CHEM1051) && (CHEM1021 || CHEM1041 || CHEM1061) && (MATH1031 || MATH1041 || MATH1131 || MATH1141 || MATH1231 || MATH1241))"
			elif (codeInUrl[0] == "CHEM2828" or codeInUrl[0] == "CHEM2839"):
				prereq = "((CHEM1011 || CHEM1031) && (CHEM1021 || CHEM1041))"
			elif (codeInUrl[0] == "CODE3100"):
				#!!!
				prereq = "(year 1 && 2 core courses)"
			elif (codeInUrl[0] == "COMP1400" or codeInUrl[0] == "COMP1911"):
				#!!!
				prereq = "!(CSE_PROGRAM)"
			elif (codeInUrl[0] == "COMP2111"):
				prereq = "((COMP1911 || COMP1917) && MATH1081)"
			elif (codeInUrl[0] == "COMP2121"):
				prereq = "(COMP1917 || COMP1921 || (COMP1911 && MTRN2500))"
			elif (codeInUrl[0] == "COMP3231"):
				#!!!
				prereq = "(((COMP1921 || COMP1927) && (COMP2121 || ELEC2142)) || (COMP1921 || COMP1927))"
			elif (codeInUrl[0] == "COMP3431"):
				prereq = "(COMP2911 && 70_WAM)"
			elif (codeInUrl[0] == "COMP3441" or codeInUrl[0] == "COMP3511"):
				prereq = "48_UOC"
			elif (codeInUrl[0] == "COMP3821"):
				prereq = "(COMP1927 || COMP1921{70})"
			elif (codeInUrl[0] == "COMP3891"):
				prereq = "((COMP1921{70} || COMP1927) && (COMP2121{70} || ELEC2142))"
			elif (codeInUrl[0] == "COMP3901" or codeInUrl[0] == "COMP3902"):
				#!!!!
				prereq = "80_CSE_WAM"
			elif (codeInUrl[0] == "COMP4411"):
				prereq = "(75_WAM && COMP1927)"
			elif (codeInUrl[0] == "COMP4431"):
				#!!!
				prereq = "(COMP1927 || (Stage_2{3267 || 3994 || 3402 || 3428 || 4810 || 4802}))"
			elif (codeInUrl[0] == "COMP4910"):
				#!!!
				prereq = "Honours"
			elif (codeInUrl[0] == "COMP4920"):
				#!!!
				prereq = "Stage_2"
			elif (codeInUrl[0] == "COMP4930"):
				#!!!
				prereq = "(4515 || 126_UOC_CSE)"
			elif (codeInUrl[0] == "COMP4941"):
				#!!!
				prereq = "(COMP4930 && (75_WAM || Honours))"
			elif (codeInUrl[0] == "COMP6721"):
				prereq = "((MATH1081 || 6_UOC_MATH2###) && 12_UOC_COMP3###)"
			elif (codeInUrl[0] == "COMP6733"):
				prereq = "(65_WAM && COMP3331)"
			elif (codeInUrl[0] == "COMP9018"):
				prereq = "COMP3421{65}"
			elif (codeInUrl[0] == "COMP9242"):
				prereq = "(COMP3231{75} || COMP3891)"
			elif (codeInUrl[0] == "COMP9844"):
				prereq = "(70_WAM && (COMP1927 || MTRN3500))"
			elif (codeInUrl[0] == "CRIM2014" or codeInUrl[0] == "CRIM2031" or codeInUrl[0] == "CRIM2032" or codeInUrl[0] == "CRIM2034" or codeInUrl[0] == "CRIM2036"):
				prereq = "(30_UOC_LEVEL_1_CRIMINOLOGY && (CRIM1010 || CRIM1011))"
			elif (codeInUrl[0] == "CRIM2020"):
				prereq = "(24_UOC_LEVEL_1_CRIMINOLOGY && CRIM1010 && CRIM1011)"
			elif (codeInUrl[0] == "CRIM2021"):
				prereq = "(CRIM2020 && !(LAW))"
			#elif (codeInUrl[0] == "CRIM2021"):
		#		prereq = "(30_UOC_LEVEL_1_CRIMINOLOGY)"
				#CRIM3### skipped
			elif (re.match('CRIM4', codeInUrl[0])):
				prereq = "(CRIMINOLOGY_HONOURS)"
				#continue with cven
			elif (codeInUrl[0] == "CVEN4002" or codeInUrl[0] == "CVEN4003" or codeInUrl[0] == "CVEN4050"):
				prereq = "(132_UOC)"
			elif (codeInUrl[0] == "CVEN4030"):
				prereq = "(132_UOC && 63_WAM)"
			elif (codeInUrl[0] == "CVEN4308"):
				prereq = "(CVEN3301 && CVEN2002)"
			elif (codeInUrl[0] == "ECON2107"):
				prereq = "(ECON1101 && (ECON1203 || ECON2292 || MATH1041 || MATH2801 || MATH2841 || MATH2901))"
			elif (codeInUrl[0] == "ECON3109" or codeInUrl[0] == "ECON3119"):
				prereq = "(ECON2101 || ECON2103 || 48_UOC_ARTS_SOCIAL_SCIENCES)"
			elif (codeInUrl[0] == "ECON3114" or codeInUrl[0] == "ECON3117"):
				prereq = "(ECON2101 || ACTL2131 || ACTL2111 || (84_UOC && (3155 || 3502 || 3554 || 4501 || 3558 || 3593 || 3835 || 3967 || 3568 || 3567 || 3584 || 4733 || 3522 || 3521 || 3462 || 3559 || 3529 || 3764 || 3136)"
			elif (codeInUrl[0] == "EDST2002"):
				prereq = "(48_UOC_LEVEL_1 && EDST1101 && EDST1104 && EDST2003 && (3446 || 3462 || 4054 || 4076 || 4058 || 4059 || 4061 || 4062))"
			elif (codeInUrl[0] == "EDST2003"):
				prereq = "(24_UOC_LEVEL_1 && (EDST1101 || EDST1104) && (3446 || 3462 || 4054 || 4058 || 4059 || 4061 || 4062 || 4076))"
			#elif (re.match('EDST20', codeInUrl[0]) and re.match('24', prereq)):
				#prereq = "24_UOC_LEVEL_1"
				#EDST skipped
			elif (re.match('ELEC', codeInUrl[0])):
				prereq.upper()
				if (codeInUrl[0] == "ELEC2142"):
					prereq = "(ELEC2141 && COMP1921)"

			#FINS2622 FINS4774 FINS4776 FINS4777 FINS4779 needs a special check
			elif (codeInUrl[0] == "FINS3202"):
				prereq = "(FINS2101 && (FINSD13554 || FINSBH3565))"
			elif (codeInUrl[0] == "FINS3303"):
				prereq = "(FINS3202 && (FINSD13554 || FINSBH3565))"
			elif (codeInUrl[0] == "FINS3775" or codeInUrl[0] == "FINS4775"):
				prereq = "(FINS2624{70} && ECON1203)"
			elif (codeInUrl[0] == "FINS4781"):
				prereq = "FINANCE_HONOURS"
			elif (codeInUrl[0] == "FOOD6804"):
				prereq = "(CHEM3811 || INDC2003)"
			elif (codeInUrl[0] == "GEOL4141"):
				prereq = "(24_UOC_LEVEL_3_GEOLOGY || 24_UOC_LEVEL_3_PHYSICAL_GEOGRAPHY)"
			elif (codeInUrl[0] == "GEOS3371"):
				prereq = "(GEOS3331 && LEVEL_3_GEOSCIENCE)"
			elif (codeInUrl[0] == "GEOS3621"):
				prereq = "((GEOS2641 || ENVS2030) && GEOS2721)"
			elif (codeInUrl[0] == "GEOS6734"):
				prereq = "(BEES2041 || MATH2089 || MATH2099 || MATH2801 || MATH2841 || MATH2859 || MATH2901)"
			elif (codeInUrl[0] == "HESC2452"):
				prereq = "((ANAT2451 || (ANAT3131 && ANAT3141)) && (BIOM2451 || SESC2451))"
			elif (codeInUrl[0] == "HESC2501"):
				prereq = "(BIOC2181 && PHSL2501)"
			elif (codeInUrl[0] == "HESC3504"):
				prereq = "(HESC2501 && HESC1511 && (PSYC1011 || HESC1531))"
			elif (codeInUrl[0] == "HESC3541"):
				prereq = " (HESC2501 && PHSL2502 && (PATH2202 || PATH2201) && PHSL2501)"
			











			

			f.write("INSERT INTO pre_reqs (course_code, career, pre_req_conditions, norm_pre_req_conditions) SELECT \'%s\', \'%s\', \'%s\', \'%s\' WHERE NOT EXISTS (SELECT course_code, career FROM pre_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], career, prereqCondition, prereq, codeInUrl[0], career))
         
			#print prereq[0].group()
		else:
			#print "went here"
			f.write("INSERT INTO pre_reqs (course_code, career, pre_req_conditions, norm_pre_req_conditions) SELECT \'%s\', \'%s\', \'\', \'\' WHERE NOT EXISTS (SELECT course_code, career FROM pre_reqs WHERE course_code = \'%s\' and career = \'%s\'); \n" % (codeInUrl[0], career, codeInUrl[0], career))

	except:
		print codeInUrl,
		print "No Handbook Entry"

f.close()