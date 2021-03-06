//
// File:		main.cpp
// Author:		Moycat
// E-mail:		mxq.1203@gmail.com
//
/*
 *			MoyOJ 评测端 - Cell version 0.1
 *			文件：	main.cpp
 *			授权：	GNU GENERAL PUBLIC LICENSE
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>
#include <unistd.h>
#include <sys/ptrace.h>
#include <sys/resource.h>
#include <sys/stat.h>
#include <sys/syscall.h>
#include <sys/time.h>
#include <sys/types.h>
#include <sys/user.h>
#include <sys/wait.h>
#include <iostream>
#include <vector>
#include <string>
#include "okcalls.h" // 系统call调用判别来自hustoj

#ifdef __i386
#define REG_SYSCALL orig_eax
#define REG_RET eax
#define REG_ARG0 ebx
#define REG_ARG1 ecx
#else
#define REG_SYSCALL orig_rax
#define REG_RET rax
#define REG_ARG0 rdi
#define REG_ARG1 rsi
#endif

#define WORK_DIR "/judge/"
#define CONFIG_FILE "judge.conf"
#define BUFFER_SIZE 512
#define JAVA_TIME_RATIO 3
#define JAVA_MEMORY_RATIO 1.5
#define COMPILE_TIME_LIMIT 10
#define COMPILE_MEMORY_LIMIT 1<<30

#define NORMAL 0
#define CONFIG_ERROR -1
#define FILE_ERROR -2
#define FORK_ERROR -3
#define COMPILE_ERROR 1
#define OUT_OF_MEMORY 2
#define TIME_OUT 3
#define RUNTIME_ERROR 4
#define NOT_ALLOWED 5

#define CPP 1
#define PASCAL 2
#define JAVA 3

using namespace std;

string config_file, summary_file, error_log;
string input_folder, output_folder;
string code_file, exe_file;
int call_counter[BUFFER_SIZE] = {0};

void WriteError(int code, string detail, bool stop = false)
{
	if(stop)
	{
		freopen(summary_file.c_str(), "w", stdout);
	}
	else
	{
		freopen(error_log.c_str(), "a+", stdout);
	}
	printf("%d\n%s", code, detail.c_str());
	fclose(stdout);
	if(stop)
	{
		exit(1);
	}
}

void WriteResult(int state, int test_turn, vector<int> result, vector<int> used_time, vector<int> used_memory)
{
	freopen(summary_file.c_str(), "w", stdout);
	printf("%d\n", NORMAL);
	for(int i = 0; i < test_turn; ++i)
	{
		printf("%d %d %d\n", result[i], used_time[i], used_memory[i]);
	}
}

int GetProcStatus(int pid, const char * mark) // 本函数来自hustoj
{
	FILE * pf;
	char fn[BUFFER_SIZE], buf[BUFFER_SIZE];
	int ret = 0;
	sprintf(fn, "/proc/%d/status", pid);
	pf = fopen(fn, "re");
	int m = strlen(mark);
	while(pf && fgets(buf, BUFFER_SIZE - 1, pf))
		{
		buf[strlen(buf) - 1] = 0;
		if(strncmp(buf, mark, m) == 0) {
			sscanf(buf + m + 1, "%d", &ret);
		}
	}
	if(pf)
		fclose(pf);
	return ret;
}

long GetFileSize(const char * filename) // 本函数来自hustoj
{
	struct stat f_stat;
	if(stat(filename, &f_stat) == -1)
	{
		return 0;
	}
	return (long)f_stat.st_size;
}

void init_syscalls_limits(int lang)	// 本函数来自hustoj
{
	int i;
	memset(call_counter, 0, sizeof(call_counter));
	if(lang == CPP) // C & C++
	{
		for(i = 0; i==0||LANG_CV[i]; i++)
		{
			call_counter[LANG_CV[i]] = HOJ_MAX_LIMIT;
		}
	}
	else if(lang == PASCAL) // Pascal
	{
		for(i = 0; i==0||LANG_PV[i]; i++)
			call_counter[LANG_PV[i]] = HOJ_MAX_LIMIT;
	}
	else if(lang == JAVA) // Java
	{
		for(i = 0; i==0||LANG_JV[i]; i++)
			call_counter[LANG_JV[i]] = HOJ_MAX_LIMIT;
	}
}

void init(int &time_limit, int &memory_limit, int &test_turn, int &lang)
{
	FILE * config;
	string solution_floder = WORK_DIR;
	config_file = solution_floder + CONFIG_FILE;
	code_file = solution_floder + "post";
	input_folder = solution_floder + "in/";
	output_folder = solution_floder + "out/";
	summary_file = output_folder + "summary.out";
	if((config = fopen(config_file.c_str(), "r")) == NULL)
	{
		WriteError(FILE_ERROR, "CONFIG FILE ERROR", true);
	}
	if(fscanf(config, "%d%d%d%d", &time_limit, &memory_limit, &test_turn, &lang) != 4)
	{
		WriteError(CONFIG_ERROR, "CONFIG ERROR", true);
	}
	memory_limit <<= 20;
	if(lang == JAVA)
	{
		time_limit = (double)time_limit * JAVA_TIME_RATIO;
		memory_limit = (double)memory_limit * JAVA_MEMORY_RATIO;
	}
	error_log = output_folder + "error.log";
}

int Compile_cpp()
{
	int pid, state;
	exe_file = output_folder + "post.out";
	const char * compile_command[] = {"g++", code_file.c_str(), "-o", exe_file.c_str(), NULL};
	struct rlimit CPU_LIMIT, MEM_LIMIT;
	CPU_LIMIT.rlim_max = CPU_LIMIT.rlim_cur = COMPILE_TIME_LIMIT;
	MEM_LIMIT.rlim_max = MEM_LIMIT.rlim_cur = COMPILE_MEMORY_LIMIT;
	pid = fork();
	if(pid < 0)
	{
		WriteError(FORK_ERROR, "FORK FAIL WHEN COMPILING", true);
	}
	if(!pid)
	{
		freopen(error_log.c_str(), "w", stderr);
		setrlimit(RLIMIT_CPU, &CPU_LIMIT);
		setrlimit(RLIMIT_AS, &MEM_LIMIT);
		alarm(COMPILE_TIME_LIMIT);
		execvp(compile_command[0], (char * const *)compile_command);
		exit(0);
	}
	else
	{
		waitpid(pid, &state, 0);
	}
	return state;
}

void Compile(int lang)
{
	int result;
	switch(lang)
	{
	case CPP:
		code_file += ".cpp";
		result = Compile_cpp();
		break;
//	case PASCAL:

//	case JAVA:

	}
	if(!WIFEXITED(result) || GetFileSize(error_log.c_str()))
	{
		WriteError(COMPILE_ERROR, "AN ERROR OCCURED WHEN COMPILING", true);
	}
	string c_flag = output_folder + "compile";
	FILE * flag;
	flag = fopen(c_flag.c_str(), "w");
	fclose(flag);
}

void RunPost(int lang)
{
	const char * Run_cpp[] = {exe_file.c_str()};
//	const char * Run_pascal
//	const char * Run_java
	alarm(0);
	ptrace(PTRACE_TRACEME, 0, NULL, NULL);
	switch(lang)
	{
	case CPP:
		execl(Run_cpp[0], Run_cpp[0], (char *)NULL);
		break;
//	case PASCAL:

//	case JAVA:

	}

	exit(0);
}

void WatchPost(int pid, int time_limit, int memory_limit, vector<int> &used_time, vector<int> &used_memory,
				vector<int> &result, string now_error)
{
	int now_used_time = 0, now_used_memory = 0;
	int now_result = NORMAL;
	int state, sig, return_code;
	struct user_regs_struct reg;
	struct rusage ruse;
	now_used_memory = GetProcStatus(pid, "VmRSS:") << 10;
	while(true)
	{
		wait4(pid, &state, 0, &ruse);
		int new_used_memory;
		new_used_memory = GetProcStatus(pid, "VmPeak:") << 10;
		if(new_used_memory > now_used_memory)
		{
			now_used_memory = new_used_memory;
		}
		if(now_used_memory > memory_limit)
		{
			now_result = OUT_OF_MEMORY;
			ptrace(PTRACE_KILL, pid, NULL, NULL);
			break;
		}
		if(WIFEXITED(state))
					break;
		if(GetFileSize(now_error.c_str()))
		{
			now_result = RUNTIME_ERROR;
			ptrace(PTRACE_KILL, pid, NULL, NULL);
			break;
		}
		return_code = WEXITSTATUS(state);
		if(return_code != 0x05 && return_code != 0)
		{
			switch (return_code)
			{
			case SIGCHLD:
			case SIGALRM:
				alarm(0);
			case SIGKILL:
			case SIGXCPU:
			case SIGPROF:
				now_result = TIME_OUT;
				break;
			case SIGXFSZ:
			default:
				now_result = RUNTIME_ERROR;
			}
			ptrace(PTRACE_KILL, pid, NULL, NULL);
			break;
		}
		if(WIFSIGNALED(state))
		{
			sig = WTERMSIG(state);
			switch (sig)
			{
			case SIGCHLD:
			case SIGALRM:
				alarm(0);
			case SIGKILL:
			case SIGXCPU:
			case SIGPROF:
				now_result = TIME_OUT;
				break;
			case SIGXFSZ:
			default:
				now_result = RUNTIME_ERROR;
			}
			break;
		}
		ptrace(PTRACE_GETREGS, pid, NULL, &reg);
		if(!call_counter[reg.REG_SYSCALL])
		{
			now_result = RUNTIME_ERROR;
			WriteError(NOT_ALLOWED, "A forbidden system call %d when running.\n");
			ptrace(PTRACE_KILL, pid, NULL, NULL);
			break;
		}
		ptrace(PTRACE_SYSCALL, pid, NULL, NULL);
	}
	now_used_time += (ruse.ru_utime.tv_sec * 1000 + ruse.ru_utime.tv_usec / 1000);
	now_used_time += (ruse.ru_stime.tv_sec * 1000 + ruse.ru_stime.tv_usec / 1000);
	result.push_back(now_result);
	used_memory.push_back(now_used_memory >> 10);
	used_time.push_back(now_used_time);
}

void Run(int lang, int time_limit, int memory_limit, int test_turn) // 本函数部分代码来自hustoj
{
	string input = input_folder + "test";
	string output = output_folder + "out";
	vector<int> result, used_time, used_memory;
	int time_limit_second = time_limit / 1000;
	int time_limit_microsecond = time_limit % 1000 * 1000;
	struct rlimit THREAD_LIMIT;
	struct itimerval CPU_LIMIT_TICK;
    CPU_LIMIT_TICK.it_value.tv_sec = time_limit_second;
    CPU_LIMIT_TICK.it_value.tv_usec = time_limit_microsecond + 10000;
    CPU_LIMIT_TICK.it_interval.tv_sec  =0;
    CPU_LIMIT_TICK.it_interval.tv_usec = 50000;
	THREAD_LIMIT.rlim_cur = THREAD_LIMIT.rlim_max = 0;
	init_syscalls_limits(lang);
	for(int i = 0; i < test_turn; ++i)
	{
		char now_turn[BUFFER_SIZE];
		sprintf(now_turn, "%d", i);
		string now_input = input + now_turn + ".in";
		string now_output = output + now_turn + ".out";
		string now_error = output + now_turn + ".log";
		int pid;
        pid = fork();
		if(pid < 0)
		{
			WriteError(FORK_ERROR, "FORK FAIL WHEN RUNNING", true);
		}
		if(!pid)
		{
			freopen(now_input.c_str(), "r", stdin);
			freopen(now_output.c_str(), "w", stdout);
			freopen(now_error.c_str(), "w", stderr);
			setrlimit(RLIMIT_NPROC, &THREAD_LIMIT);
			setitimer(ITIMER_PROF, &CPU_LIMIT_TICK, NULL);
			RunPost(lang);
		}
		else
		{
			WatchPost(pid, time_limit, memory_limit, used_time, used_memory, result, now_error);
		}
	}
	WriteResult(NORMAL, test_turn, result, used_time, used_memory);
}

int main(int argc, char** argv)
{
	int time_limit = 1000, memory_limit =  256 << 20, test_turn = 10, lang = CPP;
	init(time_limit, memory_limit, test_turn, lang);
	Compile(lang);
	Run(lang, time_limit, memory_limit, test_turn);
	return 0;
}
