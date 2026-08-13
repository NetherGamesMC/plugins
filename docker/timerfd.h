#define TFD_TIMER_ABSTIME (1 << 0)
#define TFD_CLOEXEC 02000000
#define TFD_NONBLOCK 00004000
#define CLOCK_MONOTONIC 1

typedef long time_t;

struct timespec {
    time_t tv_sec;
    long tv_nsec;
};

struct itimerspec {
    struct timespec it_interval;
    struct timespec it_value;
};

int timerfd_create(int clockid, int flags);
int timerfd_settime(int fd, int flags, const struct itimerspec *new_value, struct itimerspec *old_value);
int timerfd_gettime(int fd, struct itimerspec *curr_value);
int close(int fd);
long read(int fd, void *buf, long count);
